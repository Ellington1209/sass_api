<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    private const TYPE_MAP = [
        'avatar' => 'avatars',
        'documento' => 'documentos',
        'anexo' => 'anexos',
    ];

    public function upload(UploadedFile $file, int $tenantId, string $type = 'upload', ?int $userId = null): File
    {
        \Log::info('FileService->upload iniciado', [
            'tenant_id' => $tenantId,
            'type' => $type,
            'user_id' => $userId,
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize()
        ]);

        $category = self::TYPE_MAP[$type] ?? 'uploads';
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $directory = "tenants/{$tenantId}/{$category}";
        
        try {
            \Log::info('Fazendo upload para B2', ['directory' => $directory, 'filename' => $filename]);
            
            // Usa put diretamente com o conteúdo do arquivo
            $fullPath = $directory . '/' . $filename;
            
            // Tenta obter o conteúdo do arquivo
            // Se get() falhar, tenta getRealPath() ou getPathname()
            try {
                $content = $file->get();
            } catch (\Exception $e) {
                \Log::warning('Erro ao usar get(), tentando getRealPath()', ['error' => $e->getMessage()]);
                $realPath = $file->getRealPath();
                if ($realPath && file_exists($realPath)) {
                    $content = file_get_contents($realPath);
                } else {
                    $pathname = $file->getPathname();
                    if ($pathname && file_exists($pathname)) {
                        $content = file_get_contents($pathname);
                    } else {
                        throw new \Exception('Não foi possível ler o arquivo. Path: ' . ($realPath ?? $pathname ?? 'desconhecido'));
                    }
                }
            }
            
            \Log::info('Conteúdo do arquivo obtido', ['size' => strlen($content)]);
            
            // Faz upload - agora com throw => true, exceções serão lançadas
            $result = Storage::disk('b2')->put($fullPath, $content);
            
            $path = is_string($result) ? $result : $fullPath;
            \Log::info('Upload para B2 concluído', ['path' => $path]);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            \Log::error('Erro S3 ao fazer upload para B2', [
                'message' => $e->getMessage(),
                'code' => $e->getAwsErrorCode(),
                'request_id' => $e->getAwsRequestId(),
                'status_code' => $e->getStatusCode(),
            ]);
            throw new \Exception('Erro ao fazer upload para B2: ' . $e->getAwsErrorCode() . ' - ' . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Erro ao fazer upload para B2', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'class' => get_class($e),
            ]);
            throw $e;
        }

        try {
            $fileRecord = File::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'type' => $type,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
            \Log::info('Registro de arquivo criado no banco', ['file_id' => $fileRecord->id]);
            return $fileRecord;
        } catch (\Exception $e) {
            \Log::error('Erro ao criar registro de arquivo no banco', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function delete(string $path): bool
    {
        return Storage::disk('b2')->delete($path);
    }

    public function url(string $path, int $minutes = 60): string
    {
        try {
            return Storage::disk('b2')->temporaryUrl($path, now()->addMinutes($minutes));
        } catch (\Exception $e) {
            return Storage::disk('b2')->url($path);
        }
    }

    public function getFile(string $path)
    {
        return Storage::disk('b2')->get($path);
    }

    public function response(string $path, ?string $mime = null)
    {
        try {
            $file = $this->getFile($path);
            
            if ($file === false || $file === null) {
                abort(404, 'Arquivo não encontrado');
            }
            
            $mime = $mime ?? Storage::disk('b2')->mimeType($path) ?? 'application/octet-stream';
            
            return response($file, 200)
                ->header('Content-Type', $mime)
                ->header('Content-Disposition', 'inline')
                ->header('Cache-Control', 'public, max-age=3600');
        } catch (\Exception $e) {
            \Log::error('Erro ao servir arquivo: ' . $e->getMessage(), [
                'path' => $path,
                'trace' => $e->getTraceAsString()
            ]);
            abort(404, 'Arquivo não encontrado');
        }
    }
}


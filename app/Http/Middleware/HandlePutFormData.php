<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\HttpFoundation\Response;

class HandlePutFormData
{
    public function handle(Request $request, Closure $next): Response
    {
        // Se for PUT/PATCH com multipart/form-data, o PHP não popula $_POST e $_FILES
        // Precisamos processar manualmente
        if (in_array($request->method(), ['PUT', 'PATCH', 'DELETE']) && 
            str_contains($request->header('Content-Type', ''), 'multipart/form-data')) {
            
            // Tenta ler do php://input
            $input = file_get_contents('php://input');
            
            if (!empty($input)) {
                $boundary = $this->extractBoundary($request->header('Content-Type', ''));
                if ($boundary) {
                    $parsed = $this->parseMultipart($input, $boundary);
                    if (!empty($parsed['data'])) {
                        // Processa arrays antes de fazer merge
                        $processedData = [];
                        foreach ($parsed['data'] as $key => $value) {
                            // Se for array indexado, agrupa
                            if (preg_match('/^(.+)\[(\d+)\]$/', $key, $arrayMatches)) {
                                $arrayName = $arrayMatches[1];
                                $arrayIndex = (int)$arrayMatches[2];
                                
                                if (!isset($processedData[$arrayName]) || !is_array($processedData[$arrayName])) {
                                    $processedData[$arrayName] = [];
                                }
                                $processedData[$arrayName][$arrayIndex] = $value;
                            } else {
                                $processedData[$key] = $value;
                            }
                        }
                        
                        // Reordena arrays indexados
                        foreach ($processedData as $key => $value) {
                            if (is_array($value) && array_keys($value) !== range(0, count($value) - 1)) {
                                // Se tem índices não sequenciais, reordena
                                $processedData[$key] = array_values($value);
                            }
                        }
                        
                        // Merge os dados processados no request
                        foreach ($processedData as $key => $value) {
                            $request->merge([$key => $value]);
                        }
                    }
                    
                    // Processa arquivos ANTES de fazer merge dos dados
                    // Isso garante que o arquivo esteja disponível para validação
                    if (!empty($parsed['files'])) {
                      
                        foreach ($parsed['files'] as $key => $fileInfo) {
                            $uploadedFile = $this->createUploadedFile($fileInfo);
                            if ($uploadedFile) {
                                // Adiciona ao files do request
                                $request->files->set($key, $uploadedFile);
                                // Também remove do data para não duplicar
                                unset($parsed['data'][$key]);
                            } else {
                                Log::error('Falha ao criar UploadedFile', ['key' => $key]);
                            }
                        }
                    }
                }
            }
        }

        return $next($request);
    }

    private function extractBoundary(string $contentType): ?string
    {
        if (preg_match('/boundary=([^;]+)/i', $contentType, $matches)) {
            return trim($matches[1], ' "');
        }
        return null;
    }

    private function parseMultipart(string $content, string $boundary): array
    {
        $data = [];
        $files = [];
        $parts = preg_split('/--' . preg_quote($boundary, '/') . '(--)?\r?\n/', $content);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part) || $part === '--') {
                continue;
            }
            
            // Separa headers do body
            if (preg_match('/Content-Disposition:\s*form-data;\s*name="([^"]+)"(?:;\s*filename="([^"]+)")?/i', $part, $matches)) {
                $name = $matches[1];
                $filename = $matches[2] ?? null;
                
                // Pega o Content-Type se existir
                $contentType = null;
                if (preg_match('/Content-Type:\s*([^\r\n]+)/i', $part, $ctMatches)) {
                    $contentType = trim($ctMatches[1]);
                }
                
                // Pega o conteúdo (depois de uma linha em branco)
                $bodyParts = preg_split('/\r?\n\r?\n/', $part, 2);
                if (isset($bodyParts[1])) {
                    $value = rtrim($bodyParts[1], "\r\n--");
                    
                    // Se tem filename, é um arquivo
                    if ($filename) {
                        // Salva o arquivo temporariamente
                        $tempPath = tempnam(sys_get_temp_dir(), 'laravel_upload_');
                        file_put_contents($tempPath, $value);
                        
                        $files[$name] = [
                            'tmp_name' => $tempPath,
                            'name' => $filename,
                            'type' => $contentType ?: 'application/octet-stream',
                            'size' => strlen($value),
                            'error' => UPLOAD_ERR_OK,
                        ];
                    } else {
                        // Processa arrays indexados (ex: service_ids[0], service_ids[1])
                        if (preg_match('/^(.+)\[(\d+)\]$/', $name, $arrayMatches)) {
                            $arrayName = $arrayMatches[1];
                            $arrayIndex = (int)$arrayMatches[2];
                            
                            if (!isset($data[$arrayName]) || !is_array($data[$arrayName])) {
                                $data[$arrayName] = [];
                            }
                            $data[$arrayName][$arrayIndex] = $value;
                        } else {
                            $data[$name] = $value;
                        }
                    }
                }
            }
        }
        
        return ['data' => $data, 'files' => $files];
    }

    private function createUploadedFile(array $fileInfo): ?UploadedFile
    {
        if (!isset($fileInfo['tmp_name']) || !file_exists($fileInfo['tmp_name'])) {
            return null;
        }

        try {
            // Verifica se o arquivo ainda existe e tem conteúdo
            if (!is_readable($fileInfo['tmp_name'])) {
                return null;
            }
            
            $fileSize = filesize($fileInfo['tmp_name']);
            if ($fileSize === false || $fileSize === 0) {
                return null;
            }

            $symfonyFile = new SymfonyUploadedFile(
                $fileInfo['tmp_name'],
                $fileInfo['name'],
                $fileInfo['type'],
                $fileInfo['error'],
                false // não é test mode, é um arquivo real
            );

            $uploadedFile = UploadedFile::createFromBase($symfonyFile);
            return $uploadedFile;
        } catch (\Exception $e) {
            Log::error('Erro ao criar UploadedFile', [
                'message' => $e->getMessage(),
                'fileInfo' => $fileInfo,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}


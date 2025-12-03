<?php

namespace App\Http\Controllers\modules\File;

use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FileController
{
    public function __construct(
        private FileService $fileService
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240',
            'type' => 'required|in:avatar,documento,anexo,upload',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;

        if (!$tenantId) {
            return response()->json(['message' => 'Tenant não identificado'], 400);
        }

        $file = $this->fileService->upload(
            $request->file('file'),
            $tenantId,
            $request->input('type'),
            $request->user()->id
        );

        return response()->json([
            'message' => 'Arquivo enviado com sucesso',
            'data' => $file,
        ], 201);
    }

    /**
     * Exclui um ou vários arquivos (soft delete)
     * Aceita: DELETE /files/{id}/delete ou DELETE /files/batch/delete com body {ids: [1, 2, 3]} ou DELETE /files/delete com body {ids: [1, 2, 3]}
     */
    public function delete(Request $request, int|string|null $id = null): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;

        if (!$tenantId) {
            return response()->json(['message' => 'Tenant não identificado'], 400);
        }

        // Determina os IDs a serem excluídos
        $ids = null;
        if ($id !== null && $id !== 'batch') {
            // ID na URL - converte string para int se necessário
            $ids = is_numeric($id) ? (int) $id : null;
            if ($ids === null) {
                return response()->json([
                    'message' => 'ID inválido na URL',
                ], 400);
            }
        } elseif ($request->has('ids') && is_array($request->ids)) {
            // Array de IDs no body
            $ids = $request->ids;
        } else {
            return response()->json([
                'message' => 'ID ou array de IDs não fornecido',
            ], 400);
        }

        // Normaliza para array
        $idsArray = is_array($ids) ? $ids : [$ids];
        $idsArray = array_map('intval', $idsArray);

        $files = File::whereIn('id', $idsArray)
            ->where('tenant_id', $tenantId)
            ->get();

        if ($files->isEmpty()) {
            return response()->json([
                'message' => 'Nenhum arquivo encontrado',
                'not_found' => $idsArray,
            ], 404);
        }

        $deleted = [];
        $notFound = [];

        foreach ($files as $file) {
            // Deleta o arquivo físico do B2
            try {
                $this->fileService->delete($file->path);
            } catch (\Exception $e) {
                // Log do erro mas continua com o soft delete
                Log::warning('Erro ao deletar arquivo físico', [
                    'path' => $file->path,
                    'error' => $e->getMessage(),
                ]);
            }

            // Soft delete no banco
            $file->delete();
            $deleted[] = $file->id;
        }

        // IDs não encontrados
        $foundIds = $files->pluck('id')->toArray();
        $notFound = array_diff($idsArray, $foundIds);

        $response = [
            'message' => count($deleted) > 1 
                ? count($deleted) . ' arquivos deletados com sucesso'
                : 'Arquivo deletado com sucesso',
            'deleted' => $deleted,
        ];

        if (!empty($notFound)) {
            $response['not_found'] = array_values($notFound);
        }

        return response()->json($response, 200);
    }

    public function showUrl(Request $request, int $id): JsonResponse
    {
        $file = File::findOrFail($id);

        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;

        if ($file->tenant_id !== $tenantId) {
            return response()->json(['message' => 'Arquivo não encontrado'], 404);
        }

        $minutes = $request->input('minutes', 60);
        $url = $this->fileService->url($file->path, $minutes);

        return response()->json([
            'url' => $url,
            'expires_in' => $minutes,
        ], 200);
    }

    public function show(Request $request, string $path)
    {
        $file = File::where('path', $path)->first();

        if (!$file) {
            return response()->json(['message' => 'Arquivo não encontrado'], 404);
        }

        $user = $request->user();
        
        if ($user) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            if ($file->tenant_id !== $tenantId && !$user->is_super_admin) {
                return response()->json(['message' => 'Arquivo não encontrado'], 404);
            }
        } else {
            if ($file->type !== 'avatar') {
                return response()->json(['message' => 'Arquivo não encontrado'], 404);
            }
        }

        return $this->fileService->response($file->path, $file->mime);
    }

    public function showPublic(string $path)
    {
        try {
            $decodedPath = urldecode($path);
            $file = File::where('path', $decodedPath)->where('type', 'avatar')->first();

            if (!$file) {
                return response()->json(['message' => 'Arquivo não encontrado'], 404);
            }

            return $this->fileService->response($file->path, $file->mime);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao carregar arquivo'], 500);
        }
    }
}


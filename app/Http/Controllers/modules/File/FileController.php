<?php

namespace App\Http\Controllers\modules\File;

use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function delete(Request $request, int $id): JsonResponse
    {
        $file = File::findOrFail($id);

        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;

        if ($file->tenant_id !== $tenantId) {
            return response()->json(['message' => 'Arquivo não encontrado'], 404);
        }

        $this->fileService->delete($file->path);
        $file->delete();

        return response()->json(['message' => 'Arquivo deletado com sucesso'], 200);
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


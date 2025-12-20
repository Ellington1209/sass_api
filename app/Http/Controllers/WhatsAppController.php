<?php

namespace App\Http\Controllers;

use App\Services\EvolutionApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WhatsAppController
{
    public function __construct(
        private EvolutionApiService $evolutionApiService
    ) {}

    public function instancia(Request $request): JsonResponse
    {
        $response = $this->evolutionApiService->fetchInstances();
        
        Log::info('WhatsAppController - instancia - Resposta da API', [
            'response' => $response,
        ]);
        
        if ($response['success']) {
            return response()->json([
                'success' => true,
                'data' => $response['data'] ?? [],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $response['message'] ?? 'Erro ao buscar instâncias',
            'error' => $response['error'] ?? null,
        ], $response['status'] ?? 400);
    }

    public function criarInstancia(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'instanceName' => 'required|string|max:255',
            'number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Pegar o nome da instância do payload
        $instanceName = $request->input('instanceName');
        
        $response = $this->evolutionApiService->createInstance(
            $instanceName,
            $request->input('number')
        );
        
        if ($response['success']) {
            $qrcode = null;
            if (isset($response['data']['qrcode']['base64'])) {
                $qrcode = $response['data']['qrcode']['base64'];
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'instanceName' => $instanceName,
                    'qrcode' => $qrcode,
                    'response' => $response['data'] ?? null,
                ],
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => $response['message'] ?? 'Erro ao criar instância',
            'error' => $response['error'] ?? null,
        ], $response['status'] ?? 400);
    }

    public function deletarInstancia(Request $request, string $instanceName): JsonResponse
    {
        $response = $this->evolutionApiService->deleteInstance($instanceName);
        
        if ($response['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Instância deletada com sucesso!',
                'data' => $response['data'] ?? null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $response['message'] ?? 'Erro ao deletar instância',
            'error' => $response['error'] ?? null,
        ], $response['status'] ?? 400);
    }
}


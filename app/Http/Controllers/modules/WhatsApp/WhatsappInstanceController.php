<?php

namespace App\Http\Controllers\modules\WhatsApp;

use App\Http\Requests\StoreWhatsappInstanceRequest;
use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WhatsappInstanceController
{
    public function __construct(
        private EvolutionApiService $evolutionApiService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        }

        $instances = WhatsappInstance::when($tenantId, function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->get();

        $response = $this->evolutionApiService->fetchInstances();

        Log::info('WhatsappInstanceController - index - Resposta da API Evolution', [
            'response' => $response,
            'tenant_id' => $tenantId,
        ]);

        if ($response['success'] && isset($response['data'])) {
            $evolutionInstances = $response['data'];
            
            if (is_array($evolutionInstances)) {
                foreach ($evolutionInstances as $evolutionInstance) {
                    $instanceName = $evolutionInstance['name'] ?? $evolutionInstance['instanceName'] ?? $evolutionInstance['instance']['instanceName'] ?? null;
                    
                    if (!$instanceName) {
                        continue;
                    }

                    $connectionStatus = $evolutionInstance['connectionStatus'] ?? $evolutionInstance['state'] ?? $evolutionInstance['instance']['state'] ?? null;
                    $status = $this->mapStateToStatus($connectionStatus);
                    $ownerJid = $evolutionInstance['ownerJid'] ?? null;

                    $instance = WhatsappInstance::where('name', $instanceName)
                        ->orWhere('evolution_id', $instanceName)
                        ->when($tenantId, function ($query) use ($tenantId) {
                            $query->where('tenant_id', $tenantId);
                        })
                        ->first();

                    if ($instance) {
                        $instance->update([
                            'status' => $status,
                            'owner_jid' => $ownerJid,
                        ]);
                    }
                }
            }
        }

        $instances = WhatsappInstance::when($tenantId, function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->get();

        return response()->json([
            'success' => true,
            'data' => $instances,
        ]);
    }

    private function mapStateToStatus(?string $state): string
    {
        return match($state) {
            'open' => 'connected',
            'close' => 'disconnected',
            default => 'disconnected',
        };
    }

    public function store(StoreWhatsappInstanceRequest $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Super admin não pode criar instâncias',
            ], 403);
        }

        $instanceName = $request->input('instanceName');
        
        $response = $this->evolutionApiService->createInstance(
            $instanceName,
            $request->input('number')
        );

        if (!$response['success']) {
            return response()->json([
                'success' => false,
                'message' => $response['message'] ?? 'Erro ao criar instância',
                'error' => $response['error'] ?? null,
            ], $response['status'] ?? 400);
        }

        $evolutionData = $response['data'] ?? [];
        $evolutionId = $evolutionData['instance']['instanceName'] ?? $evolutionData['instanceName'] ?? $instanceName;
        
        $status = 'disconnected';
        if (isset($evolutionData['instance']['state'])) {
            $status = match($evolutionData['instance']['state']) {
                'open' => 'connected',
                'close' => 'disconnected',
                default => 'disconnected',
            };
        } elseif (isset($evolutionData['state'])) {
            $status = match($evolutionData['state']) {
                'open' => 'connected',
                'close' => 'disconnected',
                default => 'disconnected',
            };
        }

        $connectionStatus = $evolutionData['connectionStatus'] ?? null;
        if ($connectionStatus) {
            $status = $this->mapStateToStatus($connectionStatus);
        }

        $ownerJid = $evolutionData['ownerJid'] ?? $evolutionData['instance']['ownerJid'] ?? null;

        $instance = WhatsappInstance::create([
            'evolution_id' => $evolutionId,
            'tenant_id' => $tenantId,
            'name' => $instanceName,
            'status' => $status,
            'owner_jid' => $ownerJid,
        ]);

        $qrcode = null;
        if (isset($evolutionData['qrcode']['base64'])) {
            $qrcode = $evolutionData['qrcode']['base64'];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'instance' => $instance,
                'qrcode' => $qrcode,
            ],
        ], 201);
    }

    public function send(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        }

        $instance = WhatsappInstance::when($tenantId, function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->find($id);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'Instância não encontrada ou não pertence ao seu tenant',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'number' => 'required|string|max:20',
            'text' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        $response = $this->evolutionApiService->sendText(
            $instance->evolution_id,
            $request->input('number'),
            $request->input('text')
        );

        if (!$response['success']) {
            return response()->json([
                'success' => false,
                'message' => $response['message'] ?? 'Erro ao enviar mensagem',
                'error' => $response['error'] ?? null,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $response['data'] ?? null,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        }

        $instance = WhatsappInstance::when($tenantId, function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })->find($id);

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'Instância não encontrada ou não pertence ao seu tenant',
            ], 404);
        }

        $response = $this->evolutionApiService->deleteInstance($instance->name);

        if (!$response['success']) {
            return response()->json([
                'success' => false,
                'message' => $response['message'] ?? 'Erro ao deletar instância',
                'error' => $response['error'] ?? null,
            ], $response['status'] ?? 400);
        }

        $instance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Instância deletada com sucesso!',
            'data' => $response['data'] ?? null,
        ]);
    }
}


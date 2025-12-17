<?php

namespace App\Http\Controllers\modules\Agenda;

use App\Models\Service;
use App\Models\TenantModule;
use App\Services\AgendaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceController
{
    public function __construct(
        private AgendaService $agendaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        }

        $filters = $request->only(['active']);
        $services = $this->agendaService->getAllServices($tenantId, $filters);

        return response()->json($services);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        }

        $service = $this->agendaService->getServiceById($id, $tenantId);

        if (!$service) {
            return response()->json([
                'message' => 'Serviço não encontrado',
            ], 404);
        }

        return response()->json($service);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        }

        $validator = Validator::make($request->all(), [
            'module_id' => 'required|exists:modules,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:1',
            'active' => 'sometimes|boolean',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'price_active' => 'nullable|boolean',
            'price_start_date' => 'nullable|date',
            'price_end_date' => 'nullable|date|after_or_equal:price_start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $service = $this->agendaService->createService($tenantId, $validator->validated());
            return response()->json($service, 201);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 422 ? 422 : 400;
            return response()->json([
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        }

        $validator = Validator::make($request->all(), [
            'module_id' => 'sometimes|exists:modules,id',
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'duration_minutes' => 'sometimes|integer|min:1',
            'active' => 'sometimes|boolean',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'price_active' => 'nullable|boolean',
            'price_start_date' => 'nullable|date',
            'price_end_date' => 'nullable|date|after_or_equal:price_start_date',
            'update_price' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $service = $this->agendaService->updateService($id, $tenantId, $validator->validated());

            if (!$service) {
                return response()->json([
                    'message' => 'Serviço não encontrado',
                ], 404);
            }

            return response()->json($service);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 422 ? 422 : 400;
            return response()->json([
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    public function destroy(Request $request, int|string|null $id = null): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        }

        $ids = null;
        if ($id !== null && $id !== 'batch') {
            $ids = is_numeric($id) ? (int) $id : null;
            if ($ids === null) {
                return response()->json([
                    'message' => 'ID inválido na URL',
                ], 400);
            }
        } elseif ($request->has('ids') && is_array($request->ids)) {
            $ids = $request->ids;
        } else {
            return response()->json([
                'message' => 'ID ou array de IDs não fornecido',
            ], 400);
        }

        $idsArray = is_array($ids) ? $ids : [$ids];
        $deleted = [];
        $notFound = [];

        foreach ($idsArray as $serviceId) {
            $result = $this->agendaService->deleteService($serviceId, $tenantId);
            if ($result) {
                $deleted[] = $serviceId;
            } else {
                $notFound[] = $serviceId;
            }
        }

        if (empty($deleted)) {
            return response()->json([
                'message' => 'Nenhum serviço encontrado',
                'not_found' => $notFound,
            ], 404);
        }

        $response = [
            'message' => count($deleted) > 1 
                ? count($deleted) . ' serviços excluídos com sucesso'
                : 'Serviço excluído com sucesso',
            'deleted' => $deleted,
        ];

        if (!empty($notFound)) {
            $response['not_found'] = $notFound;
        }

        return response()->json($response);
    }

    public function servicesProvider(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant não identificado',
            ], 400);
        }

        // Busca os module_id do tenant na tabela tenant_modules
        $moduleIds = TenantModule::where('tenant_id', $tenantId)
            ->pluck('module_id')
            ->toArray();

        // Se não houver módulos, retorna array vazio
        if (empty($moduleIds)) {
            return response()->json([]);
        }

        // Busca os serviços que pertencem aos módulos do tenant
        $services = Service::whereIn('module_id', $moduleIds)
            ->select('id', 'name', 'slug')
            ->get();

        return response()->json($services);
    }
}


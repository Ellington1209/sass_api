<?php

namespace App\Http\Controllers\modules\Admin\Tenant;

use App\Services\Admin\Tenant\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TenantController
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    /**
     * Lista todos os tenants
     */
    public function index(Request $request): JsonResponse
    {
        $tenants = $this->tenantService->getAll();

        return response()->json([
            'data' => $tenants,
        ]);
    }

    /**
     * Exibe um tenant específico
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenant = $this->tenantService->getById($id);

        if (!$tenant) {
            return response()->json([
                'message' => 'Tenant not found',
            ], 404);
        }

        return response()->json([
            'data' => $tenant,
        ]);
    }

    /**
     * Cria um novo tenant
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'active' => 'boolean',
            'active_modules' => 'array',
            'active_modules.*' => 'integer|exists:modules,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tenant = $this->tenantService->create(
            $request->name,
            $request->boolean('active', true),
            $request->active_modules ?? [],
            $request->admin_user_id
        );

        return response()->json([
            'message' => 'Tenant created successfully',
            'data' => $tenant,
        ], 201);
    }

    /**
     * Atualiza um tenant
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'active' => 'boolean',
            'active_modules' => 'array',
            'active_modules.*' => 'integer|exists:modules,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tenant = $this->tenantService->update(
            $id,
            $request->only(['name', 'active', 'active_modules'])
        );

        if (!$tenant) {
            return response()->json([
                'message' => 'Tenant not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Tenant updated successfully',
            'data' => $tenant,
        ]);
    }

    /**
     * Exclui um ou vários tenants (soft delete)
     * Aceita: DELETE /admin/tenants/{id} ou DELETE /admin/tenants/batch com body {ids: [1, 2, 3]} ou DELETE /admin/tenants com body {ids: [1, 2, 3]}
     */
    public function destroy(Request $request, int|string|null $id = null): JsonResponse
    {
        try {
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

            $result = $this->tenantService->delete($ids);

            // Se não encontrou nenhum tenant
            if (empty($result['deleted']) && empty($result['errors'])) {
                return response()->json([
                    'message' => 'Nenhum tenant encontrado',
                    'not_found' => $result['not_found'],
                ], 404);
            }

            $response = [
                'message' => count($result['deleted']) > 1 
                    ? count($result['deleted']) . ' tenants excluídos com sucesso'
                    : 'Tenant excluído com sucesso',
                'deleted' => $result['deleted'],
            ];

            if (!empty($result['not_found'])) {
                $response['not_found'] = $result['not_found'];
            }

            if (!empty($result['errors'])) {
                $response['errors'] = $result['errors'];
            }

            $statusCode = !empty($result['deleted']) ? 200 : 400;
            
            return response()->json($response, $statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}


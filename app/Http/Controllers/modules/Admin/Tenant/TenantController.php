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
     * Exclui um tenant
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $deleted = $this->tenantService->delete($id);

            if (!$deleted) {
                return response()->json([
                    'message' => 'Tenant not found',
                ], 404);
            }

            return response()->json([
                'message' => 'Tenant deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}


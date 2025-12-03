<?php

namespace App\Http\Controllers\modules\Permission;

use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionController
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    /**
     * Lista permissões disponíveis para um tenant
     * GET /api/tenants/{tenantId}/permissions/{user_id} ou GET /api/tenants/{tenantId}/permissions
     */
    public function getTenantPermissions(Request $request, int $tenantId, ?int $user_id = null): JsonResponse
    {
        $result = $this->permissionService->getTenantPermissions($tenantId, $user_id);

        return response()->json($result);
    }

    /**
     * Salva permissões de um usuário
     * POST /api/users/{userId}/permissions
     */
    public function saveUserPermissions(Request $request, int $userId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->permissionService->saveUserPermissions(
            $userId,
            $request->permission_ids
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
                'not_found' => $result['not_found'] ?? [],
            ], 400);
        }

        return response()->json([
            'message' => $result['message'],
            'permissions' => $result['permissions'],
        ], 200);
    }
}


<?php

namespace App\Http\Controllers\modules\User;

use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Lista todos os usuários
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Super-admin vê todos os usuários
        $tenantId = null;
        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        }

        $users = $this->userService->getAll($tenantId);

        return response()->json($users->toArray());
    }

    /**
     * Exibe um usuário específico
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $authUser = $request->user();
        
        // Super-admin vê todos os usuários
        $tenantId = null;
        if (!$authUser->is_super_admin) {
            $tenantId = $authUser->tenant_id ?? $authUser->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        }

        $user = $this->userService->getById($id, $tenantId);

        if (!$user) {
            return response()->json([
                'message' => 'Usuário não encontrado',
            ], 404);
        }

        return response()->json($user);
    }

    /**
     * Cria um novo usuário
     */
    public function store(Request $request): JsonResponse
    {
        $authUser = $request->user();
        
        // Super-admin pode criar usuários para qualquer tenant
        $defaultTenantId = null;
        if (!$authUser->is_super_admin) {
            $defaultTenantId = $authUser->tenant_id ?? $authUser->tenantUsers()->first()?->tenant_id;
            
            if (!$defaultTenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'tenant_id' => 'nullable|exists:tenants,id',
            'is_super_admin' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Se não for super-admin, não pode criar super-admin e deve usar o tenant_id do usuário autenticado
        $tenantId = $request->tenant_id ?? $defaultTenantId;
        $isSuperAdmin = $request->boolean('is_super_admin', false);
        
        if (!$authUser->is_super_admin) {
            // Usuário normal não pode criar super-admin
            if ($isSuperAdmin) {
                return response()->json([
                    'message' => 'Erro na validação',
                    'errors' => ['is_super_admin' => ['Você não tem permissão para criar super administradores.']],
                ], 422);
            }
            // Força o tenant_id do usuário autenticado
            $tenantId = $defaultTenantId;
        }

        $user = $this->userService->create(
            $request->name,
            $request->email,
            $request->password,
            $tenantId,
            $isSuperAdmin
        );

        return response()->json($user, 201);
    }

    /**
     * Atualiza um usuário
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $authUser = $request->user();
        
        // Super-admin pode atualizar qualquer usuário
        $tenantId = null;
        if (!$authUser->is_super_admin) {
            $tenantId = $authUser->tenant_id ?? $authUser->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:6',
            'tenant_id' => 'nullable|exists:tenants,id',
            'is_super_admin' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only(['name', 'email', 'password', 'tenant_id', 'is_super_admin']);
        
        // Se não for super-admin, não pode alterar is_super_admin nem tenant_id
        if (!$authUser->is_super_admin) {
            unset($data['is_super_admin']);
            unset($data['tenant_id']);
        }

        $user = $this->userService->update(
            $id,
            $tenantId,
            $data
        );

        if (!$user) {
            return response()->json([
                'message' => 'Usuário não encontrado',
            ], 404);
        }

        return response()->json($user);
    }

    /**
     * Exclui um ou vários usuários (soft delete)
     * Aceita: DELETE /users/{id} ou DELETE /users/batch com body {ids: [1, 2, 3]} ou DELETE /users com body {ids: [1, 2, 3]}
     */
    public function destroy(Request $request, int|string|null $id = null): JsonResponse
    {
        $authUser = $request->user();
        
        // Super-admin pode excluir qualquer usuário
        $tenantId = null;
        if (!$authUser->is_super_admin) {
            $tenantId = $authUser->tenant_id ?? $authUser->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
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

        $result = $this->userService->delete($ids, $tenantId);

        // Se não encontrou nenhum usuário
        if (empty($result['deleted']) && empty($result['errors'])) {
            return response()->json([
                'message' => 'Nenhum usuário encontrado',
                'not_found' => $result['not_found'],
            ], 404);
        }

        $response = [
            'message' => count($result['deleted']) > 1 
                ? count($result['deleted']) . ' usuários excluídos com sucesso'
                : 'Usuário excluído com sucesso',
            'deleted' => $result['deleted'],
        ];

        if (!empty($result['not_found'])) {
            $response['not_found'] = $result['not_found'];
        }

        if (!empty($result['errors'])) {
            $response['errors'] = $result['errors'];
        }

        $statusCode = !empty($result['deleted']) ? 200 : 404;
        
        return response()->json($response, $statusCode);
    }
}


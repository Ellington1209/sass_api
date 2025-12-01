<?php

namespace App\Http\Controllers\modules\User;

use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        $users = $this->userService->getAll();

        return response()->json($users->toArray());
    }

    /**
     * Exibe um usuário específico
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getById($id);

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

        $user = $this->userService->create(
            $request->name,
            $request->email,
            $request->password,
            $request->tenant_id,
            $request->boolean('is_super_admin', false)
        );

        return response()->json($user, 201);
    }

    /**
     * Atualiza um usuário
     */
    public function update(Request $request, int $id): JsonResponse
    {
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

        $user = $this->userService->update(
            $id,
            $request->only(['name', 'email', 'password', 'tenant_id', 'is_super_admin'])
        );

        if (!$user) {
            return response()->json([
                'message' => 'Usuário não encontrado',
            ], 404);
        }

        return response()->json($user);
    }

    /**
     * Exclui um usuário
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = $this->userService->delete($id);

        if (!$deleted) {
            return response()->json([
                'message' => 'Usuário não encontrado',
            ], 404);
        }

        return response()->json([
            'message' => 'Usuário excluído com sucesso',
        ]);
    }
}


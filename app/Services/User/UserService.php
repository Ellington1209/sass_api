<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Obtém todos os usuários
     */
    public function getAll(): Collection
    {
        return User::with(['tenant', 'userPermissions'])
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return $this->formatUser($user);
            });
    }

    /**
     * Obtém um usuário por ID
     */
    public function getById(int $id): ?array
    {
        $user = User::with(['tenant', 'userPermissions'])->find($id);

        if (!$user) {
            return null;
        }

        return $this->formatUser($user);
    }

    /**
     * Cria um novo usuário
     */
    public function create(string $name, string $email, string $password, ?int $tenantId = null, bool $isSuperAdmin = false): array
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'tenant_id' => $tenantId,
            'is_super_admin' => $isSuperAdmin,
        ]);

        $user->load(['tenant', 'userPermissions']);

        return $this->formatUser($user);
    }

    /**
     * Atualiza um usuário
     */
    public function update(int $id, array $data): ?array
    {
        $user = User::find($id);

        if (!$user) {
            return null;
        }

        // Se tiver senha, faz hash
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Converte is_super_admin para boolean se existir
        if (isset($data['is_super_admin'])) {
            $data['is_super_admin'] = (bool) $data['is_super_admin'];
        }

        $user->update($data);
        $user->load(['tenant', 'userPermissions']);

        return $this->formatUser($user);
    }

    /**
     * Exclui um usuário
     */
    public function delete(int $id): bool
    {
        $user = User::find($id);

        if (!$user) {
            return false;
        }

        // Não permite excluir super admin
        if ($user->is_super_admin) {
            throw new \Exception('Não é possível excluir um super administrador.');
        }

        return $user->delete();
    }

    /**
     * Formata os dados do usuário para resposta
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'tenant_id' => $user->tenant_id,
            'is_super_admin' => $user->is_super_admin,
            'is_tenant' => !$user->is_super_admin && $user->tenant_id !== null,
            'tenant' => $user->tenant ? [
                'id' => $user->tenant->id,
                'name' => $user->tenant->name,
            ] : null,
            'permissions_count' => $user->userPermissions->count(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];
    }
}


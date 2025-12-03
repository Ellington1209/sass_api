<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * Obtém todos os usuários
     * @param int|null $tenantId Se null, retorna todos (apenas para super-admin)
     */
    public function getAll(?int $tenantId = null): Collection
    {
        $query = User::with(['tenant', 'userPermissions']);

        // Se tenant_id foi fornecido, filtra por ele
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->orderBy('name')
            ->get()
            ->map(function ($user) {
                return $this->formatUser($user);
            });
    }

    /**
     * Obtém um usuário por ID
     * @param int|null $tenantId Se null, busca em todos os tenants (apenas para super-admin)
     */
    public function getById(int $id, ?int $tenantId = null): ?array
    {
        $query = User::with(['tenant', 'userPermissions'])->where('id', $id);

        // Se tenant_id foi fornecido, filtra por ele
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $user = $query->first();

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
     * @param int|null $tenantId Se null, busca em todos os tenants (apenas para super-admin)
     */
    public function update(int $id, ?int $tenantId = null, array $data): ?array
    {
        $query = User::where('id', $id);

        // Se tenant_id foi fornecido, filtra por ele
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $user = $query->first();

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
     * Exclui um ou vários usuários (soft delete)
     * @param int|array $ids ID único ou array de IDs
     * @param int|null $tenantId Se null, busca em todos os tenants (apenas para super-admin)
     */
    public function delete(int|array $ids, ?int $tenantId = null): array
    {
        // Normaliza para array
        $idsArray = is_array($ids) ? $ids : [$ids];
        
        // Converte para inteiros
        $idsArray = array_map('intval', $idsArray);

        $query = User::whereIn('id', $idsArray);

        // Se tenant_id foi fornecido, filtra por ele
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            return [
                'deleted' => [],
                'not_found' => $idsArray,
                'errors' => [],
            ];
        }

        $deleted = [];
        $notFound = [];
        $errors = [];

        foreach ($users as $user) {
            // Não permite excluir super admin
            if ($user->is_super_admin) {
                $errors[] = [
                    'id' => $user->id,
                    'message' => 'Não é possível excluir um super administrador.',
                ];
                continue;
            }

            // Aplica soft delete em cascata
            DB::transaction(function () use ($user) {
                // Soft delete de todos os students relacionados ao user
                Student::where('user_id', $user->id)->delete();
                
                // Soft delete do user
                $user->delete();
            });

            $deleted[] = $user->id;
        }

        // IDs não encontrados
        $foundIds = $users->pluck('id')->toArray();
        $notFound = array_diff($idsArray, $foundIds);

        return [
            'deleted' => $deleted,
            'not_found' => array_values($notFound),
            'errors' => $errors,
        ];
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


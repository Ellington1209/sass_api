<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Obtém todos os módulos disponíveis no sistema
     */
    private function getAllSystemModules(): array
    {
        $modulesPath = base_path('modules');
        $modules = [];

        if (is_dir($modulesPath)) {
            $directories = array_filter(glob($modulesPath . '/*'), 'is_dir');
            foreach ($directories as $dir) {
                $moduleName = basename($dir);
                $modules[] = $moduleName;
            }
        }

        return $modules;
    }

    /**
     * Obtém as permissões do usuário
     */
    private function getUserPermissions(User $user): array
    {
        if ($user->is_super_admin) {
            // Super-admin tem permissões ilimitadas (retorna array vazio = todas)
            return [];
        }

        return $user->userPermissions->pluck('permission_key')->toArray();
    }

    /**
     * Obtém os módulos do usuário
     */
    private function getUserModules(User $user): array
    {
        // Super-admin vê todos os módulos do sistema
        if ($user->is_super_admin) {
            return $this->getAllSystemModules();
        }

        // Funcionário do super-admin (tenant_id = null) - só o que liberar
        if ($user->tenant_id === null) {
            // Por enquanto retorna vazio, pode ser configurado depois
            return [];
        }

        // Usuário normal de um tenant - modules ativos do tenant
        if ($user->tenant && $user->tenant->active_modules) {
            return $user->tenant->active_modules;
        }

        return [];
    }

    public function login(string $email, string $password): ?array
    {
        $user = User::with(['userPermissions', 'tenant'])
            ->where('email', $email)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tenant_id' => $user->is_super_admin ? null : $user->tenant_id,
                'is_super_admin' => $user->is_super_admin,
            ],
            'permissions' => $this->getUserPermissions($user),
            'modules' => $this->getUserModules($user),
            'token' => $token,
        ];
    }

    public function getMe(User $user): array
    {
        $user->load(['userPermissions', 'tenant']);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tenant_id' => $user->is_super_admin ? null : $user->tenant_id,
                'is_super_admin' => $user->is_super_admin,
            ],
            'permissions' => $this->getUserPermissions($user),
            'modules' => $this->getUserModules($user),
        ];
    }
}


<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\TenantModule;
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
     * Obtém as permissões do usuário agrupadas por módulo
     */
    private function getUserPermissions(User $user): array
    {
        $permissionKeys = [];
        
        if ($user->is_super_admin) {
            // Super-admin retorna todas as permissões do sistema
            $permissionKeys = Permission::pluck('permission_key')->toArray();
        } elseif ($user->isTenantAdmin()) {
            // Usuário é admin do tenant - busca permissões baseadas nos módulos do tenant
            $tenantUser = $user->tenantUsers()->first();
            
            if ($tenantUser && $tenantUser->tenant_id) {
                // Busca os módulos do tenant diretamente da tabela tenant_modules
                $tenantModules = TenantModule::where('tenant_id', $tenantUser->tenant_id)
                    ->with('module')
                    ->get();
                
                // Para cada módulo do tenant, busca todas as permissões desse módulo
                foreach ($tenantModules as $tenantModule) {
                    if ($tenantModule->module) {
                        $modulePermissions = Permission::where('module_id', $tenantModule->module_id)
                            ->pluck('permission_key')
                            ->toArray();
                        
                        $permissionKeys = array_merge($permissionKeys, $modulePermissions);
                    }
                }
            }
        } else {
            // Usuário normal - permissões através do relacionamento
            $permissionKeys = $user->userPermissions->pluck('permission_key')->toArray();
        }

        // Remove duplicatas
        $permissionKeys = array_unique($permissionKeys);

        // Se não houver permissões, retorna array vazio
        if (empty($permissionKeys)) {
            return [];
        }

        // Busca as permissões com seus módulos
        $permissions = Permission::with('module')
            ->whereIn('permission_key', $permissionKeys)
            ->get(['permission_key', 'module_id']);

        // Agrupa por módulo
        $groupedPermissions = [];
        foreach ($permissions as $permission) {
            $module = $permission->module->name ?? 'Outros';
            if (!isset($groupedPermissions[$module])) {
                $groupedPermissions[$module] = [];
            }
            $groupedPermissions[$module][] = $permission->permission_key;
        }

        return $groupedPermissions;
    }

    /**
     * Determina o tipo/role do usuário
     */
    private function getUserRole(User $user): string
    {
        // Super admin
        if ($user->is_super_admin) {
            return 'super admin';
        }

        // Tenant admin
        if ($user->isTenantAdmin()) {
            return 'tenant admin';
        }

        // Verifica se é cliente/aluno (através de Person -> Student)
        $user->load('person.student', 'person.provider');
        if ($user->person && $user->person->student) {
            return 'tenant cliente';
        }

        // Verifica se é profissional (através de Person -> Provider)
        if ($user->person && $user->person->provider) {
            return 'tenant profissional';
        }

        // Usuário normal do tenant
        return 'tenant';
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

        // Se é admin do tenant, busca os módulos do tenant através de tenant_modules
        if ($user->isTenantAdmin()) {
            $tenantUser = $user->tenantUsers()->first();
            
            if ($tenantUser && $tenantUser->tenant_id) {
                // Busca os módulos do tenant diretamente da tabela tenant_modules
                $tenantModules = TenantModule::where('tenant_id', $tenantUser->tenant_id)
                    ->with('module')
                    ->get();
                
                return $tenantModules->pluck('module.key')->filter()->toArray();
            }
        }

        // Funcionário do super-admin (tenant_id = null) - só o que liberar
        if ($user->tenant_id === null) {
            // Por enquanto retorna vazio, pode ser configurado depois
            return [];
        }

        // Usuário normal de um tenant - modules ativos do tenant
        if ($user->tenant) {
            $user->tenant->load('modules');
            return $user->tenant->modules->pluck('key')->toArray();
        }

        return [];
    }

    public function login(string $email, string $password): ?array
    {
        $user = User::with([
            'userPermissions', 
            'tenant.modules', 
            'tenantUsers.tenant.modules'
        ])
            ->where('email', $email)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        // Recarrega os relacionamentos para garantir que estão atualizados
        $user->load(['tenantUsers.tenant.modules', 'person.student', 'person.provider']);

        $token = $user->createToken('auth-token')->plainTextToken;

        // Determina se é admin do tenant
        $isTenantAdmin = $user->isTenantAdmin();
        $tenantId = null;
        
        if ($isTenantAdmin) {
            $tenantUser = $user->tenantUsers()->with('tenant.modules')->first();
            $tenantId = $tenantUser ? $tenantUser->tenant_id : null;
        } elseif (!$user->is_super_admin) {
            $tenantId = $user->tenant_id;
        }

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tenant_id' => $user->is_super_admin ? null : $tenantId,
                'role' => $this->getUserRole($user),
            ],
            'permissions' => $this->getUserPermissions($user),
            'modules' => $this->getUserModules($user),
            'token' => $token,
        ];
    }

    public function getMe(User $user): array
    {
        $user->load(['userPermissions', 'tenant.modules', 'tenantUsers.tenant.modules', 'person.student', 'person.provider']);

        // Determina se é admin do tenant
        $isTenantAdmin = $user->isTenantAdmin();
        $tenantId = null;
        
        if ($isTenantAdmin) {
            $tenantUser = $user->tenantUsers()->first();
            $tenantId = $tenantUser ? $tenantUser->tenant_id : null;
        } elseif (!$user->is_super_admin) {
            $tenantId = $user->tenant_id;
        }

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tenant_id' => $user->is_super_admin ? null : $tenantId,
                'role' => $this->getUserRole($user),
            ],
            'permissions' => $this->getUserPermissions($user),
            'modules' => $this->getUserModules($user),
        ];
    }
}


<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    /**
     * Lista permissões disponíveis para um tenant, agrupadas por módulo
     * Também retorna as permissões de um usuário específico (opcional)
     * 
     * @param int $tenantId
     * @param int|null $userId Se fornecido, retorna também as permissões deste usuário
     * @return array
     */
    public function getTenantPermissions(int $tenantId, ?int $userId = null): array
    {
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            return [
                'modules' => [],
                'user_permissions' => [],
            ];
        }

        // Busca módulos ativos do tenant
        $tenantModuleIds = TenantModule::where('tenant_id', $tenantId)
            ->pluck('module_id')
            ->toArray();

        if (empty($tenantModuleIds)) {
            return [
                'modules' => [],
                'user_permissions' => [],
            ];
        }

        // Busca módulos com suas permissões usando eager loading
        $modules = Module::whereIn('id', $tenantModuleIds)
            ->with('permissions')
            ->get()
            ->map(function ($module) {
                $permissions = $module->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'key' => $permission->permission_key,
                        'label' => $permission->descricao ?? $permission->permission_key,
                    ];
                })->toArray();

                return [
                    'id' => $module->id,
                    'name' => $module->name,
                    'key' => $module->key,
                    'description' => $module->description,
                    'permissions' => $permissions,
                ];
            })
            ->values()
            ->toArray();

        // Busca permissões do usuário se fornecido
        $userPermissionIds = [];
        if ($userId) {
            // Busca as permission_keys do usuário na tabela user_permissions
            $userPermissionKeys = UserPermission::where('user_id', $userId)
                ->pluck('permission_key')
                ->toArray();

            if (!empty($userPermissionKeys)) {
                // Busca os permission_ids correspondentes na tabela permissions usando permission_key
                $userPermissionIds = Permission::whereIn('permission_key', $userPermissionKeys)
                    ->pluck('id')
                    ->toArray();
            }
        }

        return [
            'modules' => $modules,
            'user_permissions' => $userPermissionIds,
        ];
    }

    /**
     * Salva permissões de um usuário
     * 
     * @param int $userId
     * @param array $permissionIds Array de permission IDs
     * @return array
     */
    public function saveUserPermissions(int $userId, array $permissionIds): array
    {
        $user = User::find($userId);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuário não encontrado',
            ];
        }

        // Converte permission_ids para permission_keys
        $permissions = Permission::whereIn('id', $permissionIds)->get();
        $permissionKeys = $permissions->pluck('permission_key')->toArray();

        // Valida se todas as permissões foram encontradas
        if (count($permissionKeys) !== count($permissionIds)) {
            $foundIds = $permissions->pluck('id')->toArray();
            $notFound = array_diff($permissionIds, $foundIds);
            
            return [
                'success' => false,
                'message' => 'Algumas permissões não foram encontradas',
                'not_found' => array_values($notFound),
            ];
        }

        DB::transaction(function () use ($userId, $permissionKeys) {
            // Remove todas as permissões atuais do usuário
            UserPermission::where('user_id', $userId)->delete();

            // Adiciona as novas permissões
            foreach ($permissionKeys as $permissionKey) {
                UserPermission::create([
                    'user_id' => $userId,
                    'permission_key' => $permissionKey,
                ]);
            }
        });

        return [
            'success' => true,
            'message' => 'Permissões salvas com sucesso',
            'permissions' => $permissionKeys,
        ];
    }
}


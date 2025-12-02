<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use App\Models\TenantModule;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Super-admin ignora permissões
        if ($user->is_super_admin) {
            return $next($request);
        }

        // Carrega os relacionamentos necessários
        $user->load(['userPermissions', 'tenantUsers']);
        
        // Carrega as permissões do usuário
        $userPermissions = $this->getUserPermissions($user);

        // Verifica se tem a permissão específica ou a permissão "manage" correspondente
        $hasPermission = in_array($permission, $userPermissions);
        
        // Se não tem a permissão específica, verifica se tem a permissão "manage"
        if (!$hasPermission) {
            // Extrai o módulo da permissão (ex: "admin.users.view" -> "admin.users")
            $permissionParts = explode('.', $permission);
            if (count($permissionParts) >= 2) {
                array_pop($permissionParts); // Remove a última parte (view, create, etc)
                $managePermission = implode('.', $permissionParts) . '.manage';
                $hasPermission = in_array($managePermission, $userPermissions);
            }
        }

        if (!$hasPermission) {
            return response()->json([
                'message' => 'Você não tem permissão para acessar este recurso.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Obtém as permissões do usuário (mesma lógica do AuthService)
     */
    private function getUserPermissions($user): array
    {
        $permissionKeys = [];
        
        if ($user->isTenantAdmin()) {
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
            $user->load('userPermissions');
            $permissionKeys = $user->userPermissions->pluck('permission_key')->toArray();
        }

        // Remove duplicatas
        return array_unique($permissionKeys);
    }
}


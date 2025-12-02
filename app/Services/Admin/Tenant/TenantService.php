<?php

namespace App\Services\Admin\Tenant;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\TenantModule;
use App\Models\User;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TenantService
{
    /**
     * Obtém todos os tenants
     */
    public function getAll(): Collection
    {
        return Tenant::with(['tenantUsers.user', 'modules'])
            ->orderBy('name')
            ->get()
            ->map(function ($tenant) {
                return $this->formatTenant($tenant);
            });
    }

    /**
     * Obtém um tenant por ID
     */
    public function getById(int $id): ?array
    {
        $tenant = Tenant::with(['tenantUsers.user', 'modules'])->find($id);

        if (!$tenant) {
            return null;
        }

        return $this->formatTenant($tenant);
    }

    /**
     * Cria um novo tenant
     */
    public function create(string $name, bool $active = true, array $activeModules = [], ?int $adminUserId = null): array
    {
        DB::beginTransaction();
        try {
            $tenant = Tenant::create([
                'name' => $name,
                'active' => $active,
            ]);
            
            // Associa os módulos ao tenant
            if (!empty($activeModules)) {
                $this->syncTenantModules($tenant->id, $activeModules);
            }
            
            if ($adminUserId) {
                $this->createTenantUser($tenant->id, $adminUserId);
            }
         
           DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }


        $tenant->load(['tenantUsers.user', 'modules']);

        return $this->formatTenant($tenant);
    }

    private function createTenantUser(int $tenantId, ?int $userId): void
    {
        if ($userId) {
            TenantUser::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
            ]);
            
            // Atualiza o campo tenant_id na tabela users
            User::where('id', $userId)->update(['tenant_id' => $tenantId]);
        }
    }

    private function syncTenantModules(int $tenantId, array $moduleIds): void
    {
        // Remove todos os módulos existentes
        TenantModule::where('tenant_id', $tenantId)->delete();
        
        // Adiciona os novos módulos
        foreach ($moduleIds as $moduleId) {
            TenantModule::create([
                'tenant_id' => $tenantId,
                'module_id' => $moduleId,
            ]);
        }
    }

    /**
     * Atualiza um tenant
     */
    public function update(int $id, array $data): ?array
    {
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return null;
        }

        // Converte 'active' para boolean se existir
        if (isset($data['active'])) {
            $data['active'] = (bool) $data['active'];
        }

        // Se houver active_modules, sincroniza os módulos
        if (isset($data['active_modules'])) {
            $this->syncTenantModules($id, $data['active_modules']);
            unset($data['active_modules']);
        }

        $tenant->update($data);
        $tenant->load(['tenantUsers.user', 'modules']);

        return $this->formatTenant($tenant);
    }

    /**
     * Exclui um tenant
     */
    public function delete(int $id): bool
    {
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return false;
        }

        // Verifica se há usuários associados
        $usersCount = $tenant->tenantUsers()->count();
        if ($usersCount > 0) {
            throw new \Exception("Cannot delete tenant with {$usersCount} associated user(s). Please remove users first.");
        }

        return $tenant->delete();
    }

    /**
     * Formata os dados do tenant para resposta
     */
    private function formatTenant(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'active' => $tenant->active,
            'active_modules' => $tenant->modules->map(function ($module) {
                return [
                    'id' => $module->id,
                    'key' => $module->key,
                    'name' => $module->name,
                    'description' => $module->description,
                ];
            })->values()->toArray(),
            'users' => $tenant->tenantUsers->map(function ($tenantUser) {
                return [
                    'id' => $tenantUser->user->id,
                    'name' => $tenantUser->user->name,
                    'email' => $tenantUser->user->email,
                ];
            })->values()->toArray(),
            'users_count' => $tenant->tenantUsers->count(),
            'created_at' => $tenant->created_at?->toISOString(),
            'updated_at' => $tenant->updated_at?->toISOString(),
        ];
    }
}


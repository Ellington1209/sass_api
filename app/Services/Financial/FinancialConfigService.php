<?php

namespace App\Services\Financial;

use App\Models\Financial\FinancialCategory;
use App\Models\Financial\FinancialOrigin;
use App\Models\Financial\PaymentMethod;
use App\Models\Financial\ProviderCommissionConfig;

class FinancialConfigService
{
    // ========== ORIGENS ==========

    public function createOrigin(int $tenantId, array $data): array
    {
        $data['tenant_id'] = $tenantId;
        $origin = FinancialOrigin::create($data);
        return $this->formatOrigin($origin);
    }

    public function updateOrigin(int $id, int $tenantId, array $data): ?array
    {
        $origin = FinancialOrigin::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$origin) {
            return null;
        }

        $origin->update($data);
        return $this->formatOrigin($origin);
    }

    public function deleteOrigin(int $id, int $tenantId): bool
    {
        $origin = FinancialOrigin::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$origin) {
            return false;
        }

        return $origin->delete();
    }

    public function getAllOrigins(int $tenantId, ?array $filters = null): array
    {
        $query = FinancialOrigin::where('tenant_id', $tenantId);

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        if (isset($filters['origin_type'])) {
            $query->where('origin_type', $filters['origin_type']);
        }

        $origins = $query->orderBy('name')->get();

        return $origins->map(function ($origin) {
            return $this->formatOrigin($origin);
        })->toArray();
    }

    // ========== CATEGORIAS ==========

    public function createCategory(int $tenantId, array $data): array
    {
        $data['tenant_id'] = $tenantId;
        $category = FinancialCategory::create($data);
        return $this->formatCategory($category);
    }

    public function updateCategory(int $id, int $tenantId, array $data): ?array
    {
        $category = FinancialCategory::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$category) {
            return null;
        }

        $category->update($data);
        return $this->formatCategory($category);
    }

    public function deleteCategory(int $id, int $tenantId): bool
    {
        $category = FinancialCategory::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$category) {
            return false;
        }

        return $category->delete();
    }

    public function getAllCategories(int $tenantId, ?array $filters = null): array
    {
        $query = FinancialCategory::where('tenant_id', $tenantId);

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $categories = $query->orderBy('name')->get();

        return $categories->map(function ($category) {
            return $this->formatCategory($category);
        })->toArray();
    }

    // ========== MÉTODOS DE PAGAMENTO ==========

    public function createPaymentMethod(int $tenantId, array $data): array
    {
        $data['tenant_id'] = $tenantId;
        $paymentMethod = PaymentMethod::create($data);
        return $this->formatPaymentMethod($paymentMethod);
    }

    public function updatePaymentMethod(int $id, int $tenantId, array $data): ?array
    {
        $paymentMethod = PaymentMethod::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$paymentMethod) {
            return null;
        }

        $paymentMethod->update($data);
        return $this->formatPaymentMethod($paymentMethod);
    }

    public function deletePaymentMethod(int $id, int $tenantId): bool
    {
        $paymentMethod = PaymentMethod::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$paymentMethod) {
            return false;
        }

        return $paymentMethod->delete();
    }

    public function getAllPaymentMethods(int $tenantId, ?array $filters = null): array
    {
        $query = PaymentMethod::where('tenant_id', $tenantId);

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        $paymentMethods = $query->orderBy('name')->get();

        return $paymentMethods->map(function ($paymentMethod) {
            return $this->formatPaymentMethod($paymentMethod);
        })->toArray();
    }

    // ========== CONFIGURAÇÕES DE COMISSÃO ==========

    public function createCommissionConfig(int $tenantId, array $data): array
    {
        $data['tenant_id'] = $tenantId;
        $config = ProviderCommissionConfig::create($data);
        $config->load(['provider.person.user', 'origin']);
        return $this->formatCommissionConfig($config);
    }

    public function updateCommissionConfig(int $id, int $tenantId, array $data): ?array
    {
        $config = ProviderCommissionConfig::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$config) {
            return null;
        }

        $config->update($data);
        $config->load(['provider.person.user', 'origin']);
        return $this->formatCommissionConfig($config);
    }

    public function deleteCommissionConfig(int $id, int $tenantId): bool
    {
        $config = ProviderCommissionConfig::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$config) {
            return false;
        }

        return $config->delete();
    }

    public function getAllCommissionConfigs(int $tenantId, ?array $filters = null): array
    {
        $query = ProviderCommissionConfig::where('tenant_id', $tenantId);

        if (isset($filters['provider_id'])) {
            $query->where('provider_id', $filters['provider_id']);
        }

        if (isset($filters['service_id'])) {
            $query->where('service_id', $filters['service_id']);
        }

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        // Busca por nome ou email do profissional
        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->whereHas('provider.person.user', function($q) use ($searchTerm) {
                $q->where('name', 'ILIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'ILIKE', "%{$searchTerm}%");
            });
        }

        $configs = $query->with(['provider.person.user', 'service', 'origin'])
            ->orderBy('provider_id')
            ->get();

        return $configs->map(function ($config) {
            return $this->formatCommissionConfig($config);
        })->toArray();
    }

    // ========== FORMATADORES ==========

    private function formatOrigin(FinancialOrigin $origin): array
    {
        return [
            'id' => $origin->id,
            'tenant_id' => $origin->tenant_id,
            'name' => $origin->name,
            'origin_type' => $origin->origin_type,
            'active' => $origin->active,
            'created_at' => $origin->created_at?->toISOString(),
            'updated_at' => $origin->updated_at?->toISOString(),
        ];
    }

    private function formatCategory(FinancialCategory $category): array
    {
        return [
            'id' => $category->id,
            'tenant_id' => $category->tenant_id,
            'name' => $category->name,
            'type' => $category->type,
            'active' => $category->active,
            'created_at' => $category->created_at?->toISOString(),
            'updated_at' => $category->updated_at?->toISOString(),
        ];
    }

    private function formatPaymentMethod(PaymentMethod $paymentMethod): array
    {
        return [
            'id' => $paymentMethod->id,
            'tenant_id' => $paymentMethod->tenant_id,
            'name' => $paymentMethod->name,
            'active' => $paymentMethod->active,
            'created_at' => $paymentMethod->created_at?->toISOString(),
            'updated_at' => $paymentMethod->updated_at?->toISOString(),
        ];
    }

    private function formatCommissionConfig(ProviderCommissionConfig $config): array
    {
        return [
            'id' => $config->id,
            'tenant_id' => $config->tenant_id,
            'provider' => [
                'id' => $config->provider->id,
                'name' => $config->provider->person->user->name ?? 'N/A',
            ],
            'service' => $config->service ? [
                'id' => $config->service->id,
                'name' => $config->service->name,
            ] : null,
            'origin' => $config->origin ? [
                'id' => $config->origin->id,
                'name' => $config->origin->name,
            ] : null,
            'commission_rate' => (float) $config->commission_rate,
            'active' => $config->active,
            'created_at' => $config->created_at?->toISOString(),
            'updated_at' => $config->updated_at?->toISOString(),
        ];
    }
}


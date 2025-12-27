<?php

namespace App\Services\Financial;

use App\Models\Financial\Commission;
use App\Models\Financial\FinancialTransaction;
use App\Models\Financial\ProviderCommissionConfig;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Cria uma comissão baseada em uma transação
     */
    public function createFromTransaction(
        FinancialTransaction $transaction,
        int $providerId,
        string $referenceType,
        int $referenceId,
        ?int $serviceId = null
    ): ?Commission {
        // Busca configuração de comissão (com hierarquia: service > padrão)
        $config = ProviderCommissionConfig::forProviderService(
            $providerId,
            $serviceId
        )->first();

        if (!$config) {
            return null; // Sem configuração = sem comissão
        }

        $commissionAmount = ($transaction->amount * $config->commission_rate) / 100;

        return Commission::create([
            'tenant_id' => $transaction->tenant_id,
            'provider_id' => $providerId,
            'transaction_id' => $transaction->id,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'base_amount' => $transaction->amount,
            'commission_amount' => $commissionAmount,
            'status' => 'PENDING',
        ]);
    }

    /**
     * Paga uma comissão
     */
    public function pay(int $id, int $tenantId, array $paymentData): ?array
    {
        return DB::transaction(function () use ($id, $tenantId, $paymentData) {
            $commission = Commission::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$commission) {
                return null;
            }

            if ($commission->status !== 'PENDING') {
                throw new \Exception('Apenas comissões pendentes podem ser pagas');
            }

            // Cria transação de saída para o pagamento da comissão
            $providerName = $commission->provider->person->user->name ?? 'N/A';
            $transactionData = [
                'type' => 'OUT',
                'amount' => $commission->commission_amount,
                'description' => "Pagamento de comissão - {$providerName}",
                'category_id' => $paymentData['category_id'],
                'payment_method_id' => $paymentData['payment_method_id'],
                'reference_type' => 'commission',
                'reference_id' => $commission->id,
                'status' => 'CONFIRMED',
                'occurred_at' => $paymentData['occurred_at'] ?? now(),
            ];

            $this->transactionService->create($tenantId, $transactionData);

            // Atualiza a comissão
            $commission->update([
                'status' => 'PAID',
                'paid_at' => now(),
            ]);

            $commission->load(['provider.person.user', 'transaction.category']);

            return $this->formatCommission($commission);
        });
    }

    /**
     * Cancela uma comissão
     */
    public function cancel(int $id, int $tenantId): ?array
    {
        $commission = Commission::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$commission) {
            return null;
        }

        if ($commission->status === 'PAID') {
            throw new \Exception('Comissões pagas não podem ser canceladas');
        }

        $commission->update(['status' => 'CANCELLED']);
        $commission->load(['provider.person.user', 'transaction.category']);

        return $this->formatCommission($commission);
    }

    /**
     * Lista todas as comissões
     */
    public function getAll(int $tenantId, ?array $filters = null): array
    {
        $query = Commission::where('tenant_id', $tenantId);

        // Filtros
        if (isset($filters['provider_id'])) {
            $query->where('provider_id', $filters['provider_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $query->with(['provider.person.user', 'transaction.category'])
              ->orderBy('created_at', 'desc');

        $commissions = $query->get();

        return $commissions->map(function ($commission) {
            return $this->formatCommission($commission);
        })->toArray();
    }

    /**
     * Busca uma comissão por ID
     */
    public function getById(int $id, int $tenantId): ?array
    {
        $commission = Commission::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->with(['provider.person.user', 'transaction.category'])
            ->first();

        if (!$commission) {
            return null;
        }

        return $this->formatCommission($commission);
    }

    /**
     * Calcula total de comissões por provider
     */
    public function getTotalsByProvider(int $tenantId, ?array $filters = null): array
    {
        $query = Commission::where('tenant_id', $tenantId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        $totals = $query->selectRaw('provider_id, status, SUM(commission_amount) as total')
            ->groupBy('provider_id', 'status')
            ->with('provider.person.user')
            ->get();

        $result = [];
        foreach ($totals as $total) {
            $providerId = $total->provider_id;
            if (!isset($result[$providerId])) {
                $result[$providerId] = [
                    'provider_id' => $providerId,
                    'provider_name' => $total->provider->person->user->name ?? 'N/A',
                    'pending' => 0,
                    'paid' => 0,
                    'cancelled' => 0,
                    'total' => 0,
                ];
            }

            $amount = (float) $total->total;
            $result[$providerId][strtolower($total->status)] = $amount;
            $result[$providerId]['total'] += $amount;
        }

        return array_values($result);
    }

    /**
     * Formata os dados da comissão para retorno
     */
    private function formatCommission(Commission $commission): array
    {
        return [
            'id' => $commission->id,
            'tenant_id' => $commission->tenant_id,
            'provider' => [
                'id' => $commission->provider->id,
                'name' => $commission->provider->person->user->name ?? 'N/A',
            ],
            'transaction_id' => $commission->transaction_id,
            'category' => $commission->transaction->category ? [
                'id' => $commission->transaction->category->id,
                'name' => $commission->transaction->category->name,
                'is_operational' => $commission->transaction->category->is_operational,
            ] : null,
            'reference_type' => $commission->reference_type,
            'reference_id' => $commission->reference_id,
            'base_amount' => (float) $commission->base_amount,
            'commission_amount' => (float) $commission->commission_amount,
            'status' => $commission->status,
            'status_name' => $commission->status_name,
            'paid_at' => $commission->paid_at?->toISOString(),
            'created_at' => $commission->created_at?->toISOString(),
            'updated_at' => $commission->updated_at?->toISOString(),
        ];
    }
}


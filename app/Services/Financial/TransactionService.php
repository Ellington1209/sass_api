<?php

namespace App\Services\Financial;

use App\Models\Financial\FinancialTransaction;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Cria uma nova transação financeira
     */
    public function create(int $tenantId, array $data): array
    {
        return DB::transaction(function () use ($tenantId, $data) {
            $data['tenant_id'] = $tenantId;
            $data['created_by'] = auth()->id();
            
            if (!isset($data['occurred_at'])) {
                $data['occurred_at'] = now();
            }

            if (!isset($data['status'])) {
                $data['status'] = 'CONFIRMED';
            }

            $transaction = FinancialTransaction::create($data);
            $transaction->load(['category', 'paymentMethod', 'creator']);

            return $this->formatTransaction($transaction);
        });
    }


    /**
     * Lista todas as transações
     */
    public function getAll(int $tenantId, ?array $filters = null): array
    {
        $query = FinancialTransaction::where('tenant_id', $tenantId);

        // Filtros
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['payment_method_id'])) {
            $query->where('payment_method_id', $filters['payment_method_id']);
        }

        if (isset($filters['start_date'])) {
            $query->where('occurred_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('occurred_at', '<=', $filters['end_date']);
        }

        $query->with(['category', 'paymentMethod', 'creator'])
              ->orderBy('occurred_at', 'desc');

        $transactions = $query->get();

        return $transactions->map(function ($transaction) {
            return $this->formatTransaction($transaction);
        })->toArray();
    }

    /**
     * Busca uma transação por ID
     */
    public function getById(int $id, int $tenantId): ?array
    {
        $transaction = FinancialTransaction::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->with(['category', 'paymentMethod', 'creator', 'commissions.provider.person.user'])
            ->first();

        if (!$transaction) {
            return null;
        }

        return $this->formatTransaction($transaction);
    }

    /**
     * Formata os dados da transação para retorno
     */
    private function formatTransaction(FinancialTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'tenant_id' => $transaction->tenant_id,
            'type' => $transaction->type,
            'type_name' => $transaction->type_name,
            'amount' => (float) $transaction->amount,
            'description' => $transaction->description,
            'category' => $transaction->category ? [
                'id' => $transaction->category->id,
                'name' => $transaction->category->name,
                'is_operational' => $transaction->category->is_operational,
            ] : null,
            'payment_method' => $transaction->paymentMethod ? [
                'id' => $transaction->paymentMethod->id,
                'name' => $transaction->paymentMethod->name,
            ] : null,
            'reference_type' => $transaction->reference_type,
            'reference_id' => $transaction->reference_id,
            'status' => $transaction->status,
            'status_name' => $transaction->status_name,
            'occurred_at' => $transaction->occurred_at?->toISOString(),
            'created_by' => $transaction->creator ? [
                'id' => $transaction->creator->id,
                'name' => $transaction->creator->name,
            ] : null,
            'commissions' => $transaction->commissions ? $transaction->commissions->map(function ($commission) {
                return [
                    'id' => $commission->id,
                    'provider' => [
                        'id' => $commission->provider->id,
                        'name' => $commission->provider->person->user->name ?? 'N/A',
                    ],
                    'commission_amount' => (float) $commission->commission_amount,
                    'status' => $commission->status,
                ];
            })->toArray() : [],
            'created_at' => $transaction->created_at?->toISOString(),
            'updated_at' => $transaction->updated_at?->toISOString(),
        ];
    }
}


<?php

namespace App\Services\Financial;

use App\Models\Financial\FinancialTransaction;
use App\Models\Financial\Commission;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    /**
     * Dashboard financeiro com resumos gerais
     */
    public function getDashboard(int $tenantId, ?string $startDate = null, ?string $endDate = null): array
    {
        if (!$startDate) {
            $startDate = now()->startOfMonth()->format('Y-m-d H:i:s');
        }
        if (!$endDate) {
            $endDate = now()->endOfMonth()->format('Y-m-d H:i:s');
        }

        $query = FinancialTransaction::where('tenant_id', $tenantId)
            ->where('status', 'CONFIRMED')
            ->whereBetween('occurred_at', [$startDate, $endDate]);

        // Totais de entrada e saída
        $income = (clone $query)->where('type', 'IN')->sum('amount');
        $expense = (clone $query)->where('type', 'OUT')->sum('amount');
        $balance = $income - $expense;

        // Entradas por categoria
        $incomeByCategory = FinancialTransaction::where('tenant_id', $tenantId)
            ->where('type', 'IN')
            ->where('status', 'CONFIRMED')
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->with('category')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category->name ?? 'N/A',
                    'total' => (float) $item->total,
                ];
            })->toArray();

        // Despesas por categoria
        $expenseByCategory = FinancialTransaction::where('tenant_id', $tenantId)
            ->where('type', 'OUT')
            ->where('status', 'CONFIRMED')
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->with('category')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category->name ?? 'N/A',
                    'total' => (float) $item->total,
                ];
            })->toArray();

        // Comissões
        $commissionsPending = Commission::where('tenant_id', $tenantId)
            ->where('status', 'PENDING')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('commission_amount');

        $commissionsPaid = Commission::where('tenant_id', $tenantId)
            ->where('status', 'PAID')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('commission_amount');

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'summary' => [
                'income' => (float) $income,
                'expense' => (float) $expense,
                'balance' => (float) $balance,
                'commissions_pending' => (float) $commissionsPending,
                'commissions_paid' => (float) $commissionsPaid,
            ],
            'income_by_category' => $incomeByCategory,
            'expense_by_category' => $expenseByCategory,
        ];
    }

    /**
     * Fluxo de caixa mensal
     */
    public function getCashFlow(int $tenantId, int $year, int $month): array
    {
        $startDate = now()->setYear($year)->setMonth($month)->startOfMonth()->format('Y-m-d H:i:s');
        $endDate = now()->setYear($year)->setMonth($month)->endOfMonth()->format('Y-m-d H:i:s');

        $transactions = FinancialTransaction::where('tenant_id', $tenantId)
            ->where('status', 'CONFIRMED')
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(occurred_at) as date'),
                'type',
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get();

        $cashFlow = [];
        $currentDate = now()->setYear($year)->setMonth($month)->startOfMonth();
        $endDateObj = now()->setYear($year)->setMonth($month)->endOfMonth();

        while ($currentDate <= $endDateObj) {
            $dateStr = $currentDate->format('Y-m-d');
            $cashFlow[$dateStr] = [
                'date' => $dateStr,
                'income' => 0,
                'expense' => 0,
                'balance' => 0,
            ];
            $currentDate->addDay();
        }

        foreach ($transactions as $transaction) {
            $dateStr = $transaction->date;
            if (isset($cashFlow[$dateStr])) {
                if ($transaction->type === 'IN') {
                    $cashFlow[$dateStr]['income'] = (float) $transaction->total;
                } else {
                    $cashFlow[$dateStr]['expense'] = (float) $transaction->total;
                }
            }
        }

        // Calcula o saldo acumulado
        $accumulatedBalance = 0;
        foreach ($cashFlow as &$day) {
            $day['balance'] = $day['income'] - $day['expense'];
            $accumulatedBalance += $day['balance'];
            $day['accumulated_balance'] = $accumulatedBalance;
        }

        return [
            'year' => $year,
            'month' => $month,
            'cash_flow' => array_values($cashFlow),
        ];
    }

    /**
     * Relatório de comissões por profissional
     */
    public function getCommissionsReport(int $tenantId, ?array $filters = null): array
    {
        $query = Commission::where('tenant_id', $tenantId);

        if (isset($filters['provider_id'])) {
            $query->where('provider_id', $filters['provider_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category_id'])) {
            $query->whereHas('transaction', function($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        $commissions = $query->select(
            'provider_id',
            'status',
            DB::raw('COUNT(*) as quantity'),
            DB::raw('SUM(commission_amount) as total')
        )
        ->groupBy('provider_id', 'status')
        ->with('provider.person.user')
        ->get();

        $report = [];
        foreach ($commissions as $commission) {
            $providerId = $commission->provider_id;
            $providerName = $commission->provider->person->user->name ?? 'N/A';

            if (!isset($report[$providerId])) {
                $report[$providerId] = [
                    'provider_id' => $providerId,
                    'provider_name' => $providerName,
                    'pending' => ['quantity' => 0, 'total' => 0],
                    'paid' => ['quantity' => 0, 'total' => 0],
                    'cancelled' => ['quantity' => 0, 'total' => 0],
                    'total_quantity' => 0,
                    'total_amount' => 0,
                ];
            }

            $status = strtolower($commission->status);
            $report[$providerId][$status] = [
                'quantity' => $commission->quantity,
                'total' => (float) $commission->total,
            ];
            $report[$providerId]['total_quantity'] += $commission->quantity;
            $report[$providerId]['total_amount'] += (float) $commission->total;
        }

        return array_values($report);
    }
}


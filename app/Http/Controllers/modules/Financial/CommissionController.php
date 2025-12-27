<?php

namespace App\Http\Controllers\modules\Financial;

use App\Services\Financial\CommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionController
{
    public function __construct(
        private CommissionService $commissionService
    ) {}

    /**
     * Lista todas as comissões
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        
        $filters = $request->only([
            'provider_id',
            'status',
        ]);

        $commissions = $this->commissionService->getAll($tenantId, $filters);

        return response()->json($commissions);
    }

    /**
     * Busca uma comissão por ID
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $commission = $this->commissionService->getById($id, $tenantId);

        if (!$commission) {
            return response()->json(['message' => 'Comissão não encontrada'], 404);
        }

        return response()->json($commission);
    }

    /**
     * Paga uma comissão
     */
    public function pay(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:financial_categories,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'occurred_at' => 'nullable|date',
        ]);

        $tenantId = $request->user()->tenant_id;

        try {
            $commission = $this->commissionService->pay($id, $tenantId, $validated);

            if (!$commission) {
                return response()->json(['message' => 'Comissão não encontrada'], 404);
            }

            return response()->json($commission);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Cancela uma comissão
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        try {
            $commission = $this->commissionService->cancel($id, $tenantId);

            if (!$commission) {
                return response()->json(['message' => 'Comissão não encontrada'], 404);
            }

            return response()->json($commission);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Relatório de totais por provider
     */
    public function totalsByProvider(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        
        $filters = $request->only([
            'status',
            'start_date',
            'end_date',
        ]);

        $totals = $this->commissionService->getTotalsByProvider($tenantId, $filters);

        return response()->json($totals);
    }
}


<?php

namespace App\Http\Controllers\modules\Financial;

use App\Services\Financial\FinancialReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialReportController
{
    public function __construct(
        private FinancialReportService $reportService
    ) {}

    /**
     * Dashboard financeiro
     */
    public function dashboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $tenantId = $request->user()->tenant_id;
        
        $dashboard = $this->reportService->getDashboard(
            $tenantId,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        return response()->json($dashboard);
    }

    /**
     * Fluxo de caixa mensal
     */
    public function cashFlow(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $tenantId = $request->user()->tenant_id;
        
        $cashFlow = $this->reportService->getCashFlow(
            $tenantId,
            $validated['year'],
            $validated['month']
        );

        return response()->json($cashFlow);
    }

    /**
     * Relatório de comissões
     */
    public function commissionsReport(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        
        $filters = $request->only([
            'provider_id',
            'status',
            'start_date',
            'end_date',
        ]);

        $report = $this->reportService->getCommissionsReport($tenantId, $filters);

        return response()->json($report);
    }
}


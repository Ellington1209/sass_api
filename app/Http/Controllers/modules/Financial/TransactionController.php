<?php

namespace App\Http\Controllers\modules\Financial;

use App\Services\Financial\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Lista todas as transações
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        
        $filters = $request->only([
            'type',
            'status',
            'category_id',
            'payment_method_id',
            'start_date',
            'end_date',
        ]);

        $transactions = $this->transactionService->getAll($tenantId, $filters);

        return response()->json($transactions);
    }

    /**
     * Busca uma transação por ID
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $transaction = $this->transactionService->getById($id, $tenantId);

        if (!$transaction) {
            return response()->json(['message' => 'Transação não encontrada'], 404);
        }

        return response()->json($transaction);
    }

    /**
     * Cria uma nova transação
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:IN,OUT',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:financial_categories,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'reference_type' => 'nullable|string|max:50',
            'reference_id' => 'nullable|integer',
            'status' => 'nullable|in:PENDING,CONFIRMED,CANCELLED',
            'occurred_at' => 'nullable|date',
        ]);

        $tenantId = $request->user()->tenant_id;

        try {
            $transaction = $this->transactionService->create($tenantId, $validated);
            return response()->json($transaction, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

}


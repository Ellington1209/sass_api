<?php

namespace App\Http\Controllers\modules\Financial;

use App\Services\Financial\FinancialConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialConfigController
{
    public function __construct(
        private FinancialConfigService $configService
    ) {}

    // ========== ORIGENS ==========

    public function indexOrigins(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $filters = $request->only(['active', 'origin_type']);
        $origins = $this->configService->getAllOrigins($tenantId, $filters);
        return response()->json($origins);
    }

    public function storeOrigin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'origin_type' => 'required|in:OPERATIONAL,MANUAL',
            'active' => 'nullable|boolean',
        ]);

        $tenantId = $request->user()->tenant_id;
        $origin = $this->configService->createOrigin($tenantId, $validated);
        return response()->json($origin, 201);
    }

    public function updateOrigin(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'origin_type' => 'nullable|in:OPERATIONAL,MANUAL',
            'active' => 'nullable|boolean',
        ]);

        $tenantId = $request->user()->tenant_id;
        $origin = $this->configService->updateOrigin($id, $tenantId, $validated);

        if (!$origin) {
            return response()->json(['message' => 'Origem não encontrada'], 404);
        }

        return response()->json($origin);
    }

    public function destroyOrigin(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $result = $this->configService->deleteOrigin($id, $tenantId);

        if (!$result) {
            return response()->json(['message' => 'Origem não encontrada'], 404);
        }

        return response()->json(['message' => 'Origem deletada com sucesso']);
    }

    // ========== CATEGORIAS ==========

    public function indexCategories(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $filters = $request->only(['active', 'type']);
        $categories = $this->configService->getAllCategories($tenantId, $filters);
        return response()->json($categories);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:IN,OUT',
            'active' => 'nullable|boolean',
        ]);

        $tenantId = $request->user()->tenant_id;
        $category = $this->configService->createCategory($tenantId, $validated);
        return response()->json($category, 201);
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|in:IN,OUT',
            'active' => 'nullable|boolean',
        ]);

        $tenantId = $request->user()->tenant_id;
        $category = $this->configService->updateCategory($id, $tenantId, $validated);

        if (!$category) {
            return response()->json(['message' => 'Categoria não encontrada'], 404);
        }

        return response()->json($category);
    }

    public function destroyCategory(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $result = $this->configService->deleteCategory($id, $tenantId);

        if (!$result) {
            return response()->json(['message' => 'Categoria não encontrada'], 404);
        }

        return response()->json(['message' => 'Categoria deletada com sucesso']);
    }

    // ========== MÉTODOS DE PAGAMENTO ==========

    public function indexPaymentMethods(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $filters = $request->only(['active']);
        $paymentMethods = $this->configService->getAllPaymentMethods($tenantId, $filters);
        return response()->json($paymentMethods);
    }

    public function storePaymentMethod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'active' => 'nullable|boolean',
        ]);

        $tenantId = $request->user()->tenant_id;
        $paymentMethod = $this->configService->createPaymentMethod($tenantId, $validated);
        return response()->json($paymentMethod, 201);
    }

    public function updatePaymentMethod(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
        ]);

        $tenantId = $request->user()->tenant_id;
        $paymentMethod = $this->configService->updatePaymentMethod($id, $tenantId, $validated);

        if (!$paymentMethod) {
            return response()->json(['message' => 'Método de pagamento não encontrado'], 404);
        }

        return response()->json($paymentMethod);
    }

    public function destroyPaymentMethod(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $result = $this->configService->deletePaymentMethod($id, $tenantId);

        if (!$result) {
            return response()->json(['message' => 'Método de pagamento não encontrado'], 404);
        }

        return response()->json(['message' => 'Método de pagamento deletado com sucesso']);
    }

    // ========== CONFIGURAÇÕES DE COMISSÃO ==========

    public function indexCommissionConfigs(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $filters = $request->only(['provider_id', 'service_id', 'active', 'search']);
        $configs = $this->configService->getAllCommissionConfigs($tenantId, $filters);
        return response()->json($configs);
    }

    public function storeCommissionConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider_id' => 'required|exists:providers,id',
            'service_id' => 'nullable|exists:services,id',
            'origin_id' => 'nullable|exists:financial_origins,id',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'active' => 'nullable|boolean',
        ]);

        $tenantId = $request->user()->tenant_id;

        try {
            $config = $this->configService->createCommissionConfig($tenantId, $validated);
            return response()->json($config, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function updateCommissionConfig(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'active' => 'nullable|boolean',
        ]);

        $tenantId = $request->user()->tenant_id;

        try {
            $config = $this->configService->updateCommissionConfig($id, $tenantId, $validated);

            if (!$config) {
                return response()->json(['message' => 'Configuração não encontrada'], 404);
            }

            return response()->json($config);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroyCommissionConfig(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $result = $this->configService->deleteCommissionConfig($id, $tenantId);

        if (!$result) {
            return response()->json(['message' => 'Configuração não encontrada'], 404);
        }

        return response()->json(['message' => 'Configuração deletada com sucesso']);
    }
}


<?php

namespace App\Http\Controllers\modules\Agenda;

use App\Services\Agenda\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BlockController
{
    public function __construct(
        private AvailabilityService $availabilityService
    ) {}

    /**
     * Lista todos os bloqueios de um profissional
     */
    public function index(Request $request, int $providerId): JsonResponse
    {
        $start = $request->input('start');
        $end = $request->input('end');

        $blocks = $this->availabilityService->getBlocks($providerId, $start, $end);

        return response()->json($blocks->toArray());
    }

    /**
     * Cria um bloqueio
     */
    public function store(Request $request, int $providerId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant não identificado',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        $block = $this->availabilityService->createBlock(
            $providerId,
            $tenantId,
            $request->user()->id,
            $validator->validated()
        );

        return response()->json($block, 201);
    }

    /**
     * Atualiza um bloqueio
     */
    public function update(Request $request, int $providerId, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant não identificado',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'start_at' => 'sometimes|date',
            'end_at' => 'sometimes|date|after:start_at',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        $block = $this->availabilityService->updateBlock($id, $providerId, $tenantId, $validator->validated());

        if (!$block) {
            return response()->json([
                'message' => 'Bloqueio não encontrado',
            ], 404);
        }

        return response()->json($block);
    }

    /**
     * Exclui um bloqueio
     */
    public function destroy(Request $request, int $providerId, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant não identificado',
            ], 400);
        }

        $deleted = $this->availabilityService->deleteBlock($id, $providerId, $tenantId);

        if (!$deleted) {
            return response()->json([
                'message' => 'Bloqueio não encontrado',
            ], 404);
        }

        return response()->json([
            'message' => 'Bloqueio excluído com sucesso',
        ]);
    }
}


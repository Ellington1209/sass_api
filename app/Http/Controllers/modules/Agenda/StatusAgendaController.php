<?php

namespace App\Http\Controllers\modules\Agenda;

use App\Services\Agenda\StatusAgendaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusAgendaController
{
    public function __construct(
        private StatusAgendaService $statusAgendaService
    ) {}

    /**
     * Lista todos os status de agenda ativos
     */
    public function index(Request $request): JsonResponse
    {
        $statuses = $this->statusAgendaService->getAll();

        return response()->json($statuses->toArray());
    }

    /**
     * Exibe um status específico
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $status = $this->statusAgendaService->getById($id);

        if (!$status) {
            return response()->json([
                'message' => 'Status não encontrado',
            ], 404);
        }

        return response()->json($status);
    }
}


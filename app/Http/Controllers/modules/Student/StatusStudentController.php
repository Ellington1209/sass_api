<?php

namespace App\Http\Controllers\modules\Student;

use App\Services\Student\StatusStudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusStudentController
{
    public function __construct(
        private StatusStudentService $statusStudentService
    ) {}

    /**
     * Lista todos os status de alunos ativos
     */
    public function index(Request $request): JsonResponse
    {
        $statuses = $this->statusStudentService->getAll();

        return response()->json($statuses->toArray());
    }

    /**
     * Exibe um status específico
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $status = $this->statusStudentService->getById($id);

        if (!$status) {
            return response()->json([
                'message' => 'Status não encontrado',
            ], 404);
        }

        return response()->json($status);
    }
}


<?php

namespace App\Http\Controllers\modules\Agenda;

use App\Services\AgendaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppointmentController
{
    public function __construct(
        private AgendaService $agendaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        }

        $filters = $request->only(['provider_id', 'date_start', 'date_end']);
        $appointments = $this->agendaService->getAllAppointments($tenantId, $filters);

        return response()->json($appointments);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        }

        $appointment = $this->agendaService->getAppointmentById($id, $tenantId);

        if (!$appointment) {
            return response()->json([
                'message' => 'Agendamento não encontrado',
            ], 404);
        }

        return response()->json($appointment);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'Super admin não pode criar agendamentos',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'provider_id' => 'required|exists:providers,id',
            'client_id' => 'required|exists:users,id',
            'date_start' => 'required|date',
            'status_agenda_id' => 'nullable|exists:status_agenda,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $appointment = $this->agendaService->createAppointment($tenantId, $validator->validated());
            return response()->json($appointment, 201);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 422 ? 422 : 400;
            return response()->json([
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'Super admin não pode atualizar agendamentos',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'service_id' => 'sometimes|exists:services,id',
            'provider_id' => 'sometimes|exists:providers,id',
            'client_id' => 'sometimes|exists:users,id',
            'date_start' => 'sometimes|date',
            'status_agenda_id' => 'nullable|exists:status_agenda,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $appointment = $this->agendaService->updateAppointment($id, $tenantId, $validator->validated());

            if (!$appointment) {
                return response()->json([
                    'message' => 'Agendamento não encontrado',
                ], 404);
            }

            return response()->json($appointment);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 422 ? 422 : 400;
            return response()->json([
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    public function destroy(Request $request, int|string|null $id = null): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'Super admin não pode excluir agendamentos',
            ], 403);
        }

        $ids = null;
        if ($id !== null && $id !== 'batch') {
            $ids = is_numeric($id) ? (int) $id : null;
            if ($ids === null) {
                return response()->json([
                    'message' => 'ID inválido na URL',
                ], 400);
            }
        } elseif ($request->has('ids') && is_array($request->ids)) {
            $ids = $request->ids;
        } else {
            return response()->json([
                'message' => 'ID ou array de IDs não fornecido',
            ], 400);
        }

        $idsArray = is_array($ids) ? $ids : [$ids];
        $deleted = [];
        $notFound = [];

        foreach ($idsArray as $appointmentId) {
            $result = $this->agendaService->deleteAppointment($appointmentId, $tenantId);
            if ($result) {
                $deleted[] = $appointmentId;
            } else {
                $notFound[] = $appointmentId;
            }
        }

        if (empty($deleted)) {
            return response()->json([
                'message' => 'Nenhum agendamento encontrado',
                'not_found' => $notFound,
            ], 404);
        }

        $response = [
            'message' => count($deleted) > 1 
                ? count($deleted) . ' agendamentos excluídos com sucesso'
                : 'Agendamento excluído com sucesso',
            'deleted' => $deleted,
        ];

        if (!empty($notFound)) {
            $response['not_found'] = $notFound;
        }

        return response()->json($response);
    }
}


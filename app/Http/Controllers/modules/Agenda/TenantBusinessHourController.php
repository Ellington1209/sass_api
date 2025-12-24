<?php

namespace App\Http\Controllers\modules\Agenda;

use App\Services\Agenda\TenantBusinessHourService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TenantBusinessHourController
{
    public function __construct(
        private TenantBusinessHourService $businessHourService
    ) {}

    /**
     * Lista todos os horários de funcionamento do tenant
     */
    public function index(Request $request, int $tenantId): JsonResponse
    {
        $businessHours = $this->businessHourService->getBusinessHours($tenantId);

        return response()->json($businessHours->toArray());
    }

    /**
     * Cria um horário de funcionamento
     */
    public function store(Request $request, int $tenantId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'weekday' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        $businessHour = $this->businessHourService->createBusinessHour($tenantId, $validator->validated());

        return response()->json($businessHour, 201);
    }

    /**
     * Atualiza um horário de funcionamento
     */
    public function update(Request $request, int $tenantId, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'weekday' => 'sometimes|integer|min:0|max:6',
            'start_time' => 'sometimes|date_format:H:i:s',
            'end_time' => 'sometimes|date_format:H:i:s|after:start_time',
            'active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        $businessHour = $this->businessHourService->updateBusinessHour($id, $tenantId, $validator->validated());

        if (!$businessHour) {
            return response()->json([
                'message' => 'Horário de funcionamento não encontrado',
            ], 404);
        }

        return response()->json($businessHour);
    }

    /**
     * Exclui um horário de funcionamento
     */
    public function destroy(Request $request, int $tenantId, int $id): JsonResponse
    {
        $deleted = $this->businessHourService->deleteBusinessHour($id, $tenantId);

        if (!$deleted) {
            return response()->json([
                'message' => 'Horário de funcionamento não encontrado',
            ], 404);
        }

        return response()->json([
            'message' => 'Horário de funcionamento excluído com sucesso',
        ]);
    }

    /**
     * Sincroniza múltiplos horários de funcionamento (cria ou atualiza)
     */
    public function sync(Request $request, int $tenantId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'business_hours' => 'required|array',
            'business_hours.*.weekday' => 'required|integer|min:0|max:6',
            'business_hours.*.start_time' => 'required|date_format:H:i:s',
            'business_hours.*.end_time' => 'required|date_format:H:i:s|after:business_hours.*.start_time',
            'business_hours.*.active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        $businessHours = $this->businessHourService->syncBusinessHours($tenantId, $request->input('business_hours'));

        return response()->json([
            'message' => 'Horários de funcionamento sincronizados com sucesso',
            'data' => $businessHours,
        ]);
    }
}


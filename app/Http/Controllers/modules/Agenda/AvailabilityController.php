<?php

namespace App\Http\Controllers\modules\Agenda;

use App\Services\Agenda\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AvailabilityController
{
    public function __construct(
        private AvailabilityService $availabilityService
    ) {}

    /**
     * Lista todas as disponibilidades de um profissional
     */
    public function index(Request $request, int $providerId): JsonResponse
    {
        $availabilities = $this->availabilityService->getAvailabilities($providerId);

        return response()->json($availabilities->toArray());
    }

    /**
     * Cria uma disponibilidade
     */
    public function store(Request $request, int $providerId): JsonResponse
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

        $data = $validator->validated();
        $data['active'] = $data['active'] ?? true;

        $availability = $this->availabilityService->createAvailability($providerId, $data);

        return response()->json($availability, 201);
    }

    /**
     * Atualiza uma disponibilidade
     */
    public function update(Request $request, int $providerId, int $id): JsonResponse
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

        $availability = $this->availabilityService->updateAvailability($id, $providerId, $validator->validated());

        if (!$availability) {
            return response()->json([
                'message' => 'Disponibilidade não encontrada',
            ], 404);
        }

        return response()->json($availability);
    }

    /**
     * Exclui uma disponibilidade
     */
    public function destroy(Request $request, int $providerId, int $id): JsonResponse
    {
        $deleted = $this->availabilityService->deleteAvailability($id, $providerId);

        if (!$deleted) {
            return response()->json([
                'message' => 'Disponibilidade não encontrada',
            ], 404);
        }

        return response()->json([
            'message' => 'Disponibilidade excluída com sucesso',
        ]);
    }

    /**
     * Sincroniza múltiplas disponibilidades (cria ou atualiza)
     */
    public function sync(Request $request, int $providerId): JsonResponse
    {
        // Aceita tanto 'availabilities' quanto 'business_hours' no payload
        $requestData = $request->all();
        if (isset($requestData['business_hours']) && !isset($requestData['availabilities'])) {
            $requestData['availabilities'] = $requestData['business_hours'];
        }

        $validator = Validator::make($requestData, [
            'availabilities' => 'required|array',
            'availabilities.*.weekday' => 'required|integer|min:0|max:6',
            'availabilities.*.start_time' => 'required|date_format:H:i:s',
            'availabilities.*.end_time' => 'required|date_format:H:i:s|after:availabilities.*.start_time',
            'availabilities.*.active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        $availabilities = $this->availabilityService->syncAvailabilities($providerId, $requestData['availabilities']);

        return response()->json([
            'message' => 'Disponibilidades sincronizadas com sucesso',
            'data' => $availabilities,
        ]);
    }
}


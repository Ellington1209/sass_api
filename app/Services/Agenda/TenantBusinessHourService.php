<?php

namespace App\Services\Agenda;

use App\Models\TenantBusinessHour;
use Illuminate\Support\Collection;

class TenantBusinessHourService
{
    /**
     * Obtém todos os horários de funcionamento de um tenant
     */
    public function getBusinessHours(int $tenantId): Collection
    {
        return TenantBusinessHour::where('tenant_id', $tenantId)
            ->where('active', true)
            ->orderBy('weekday')
            ->get()
            ->map(function ($businessHour) {
                return [
                    'id' => $businessHour->id,
                    'tenant_id' => $businessHour->tenant_id,
                    'weekday' => $businessHour->weekday,
                    'start_time' => $businessHour->start_time,
                    'end_time' => $businessHour->end_time,
                    'active' => $businessHour->active,
                    'created_at' => $businessHour->created_at?->toISOString(),
                    'updated_at' => $businessHour->updated_at?->toISOString(),
                ];
            });
    }

    /**
     * Cria um horário de funcionamento
     */
    public function createBusinessHour(int $tenantId, array $data): array
    {
        $data['tenant_id'] = $tenantId;
        $data['active'] = $data['active'] ?? true;

        $businessHour = TenantBusinessHour::create($data);

        return [
            'id' => $businessHour->id,
            'tenant_id' => $businessHour->tenant_id,
            'weekday' => $businessHour->weekday,
            'start_time' => $businessHour->start_time,
            'end_time' => $businessHour->end_time,
            'active' => $businessHour->active,
            'created_at' => $businessHour->created_at?->toISOString(),
            'updated_at' => $businessHour->updated_at?->toISOString(),
        ];
    }

    /**
     * Atualiza um horário de funcionamento
     */
    public function updateBusinessHour(int $id, int $tenantId, array $data): ?array
    {
        $businessHour = TenantBusinessHour::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$businessHour) {
            return null;
        }

        $businessHour->update($data);

        return [
            'id' => $businessHour->id,
            'tenant_id' => $businessHour->tenant_id,
            'weekday' => $businessHour->weekday,
            'start_time' => $businessHour->start_time,
            'end_time' => $businessHour->end_time,
            'active' => $businessHour->active,
            'created_at' => $businessHour->created_at?->toISOString(),
            'updated_at' => $businessHour->updated_at?->toISOString(),
        ];
    }

    /**
     * Exclui um horário de funcionamento
     */
    public function deleteBusinessHour(int $id, int $tenantId): bool
    {
        $businessHour = TenantBusinessHour::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$businessHour) {
            return false;
        }

        return $businessHour->delete();
    }

    /**
     * Cria ou atualiza múltiplos horários de funcionamento
     */
    public function syncBusinessHours(int $tenantId, array $businessHours): array
    {
        $result = [];

        foreach ($businessHours as $businessHourData) {
            $weekday = $businessHourData['weekday'];
            
            $existing = TenantBusinessHour::where('tenant_id', $tenantId)
                ->where('weekday', $weekday)
                ->first();

            if ($existing) {
                $existing->update([
                    'start_time' => $businessHourData['start_time'],
                    'end_time' => $businessHourData['end_time'],
                    'active' => $businessHourData['active'] ?? true,
                ]);
                $result[] = $this->formatBusinessHour($existing);
            } else {
                $new = TenantBusinessHour::create([
                    'tenant_id' => $tenantId,
                    'weekday' => $weekday,
                    'start_time' => $businessHourData['start_time'],
                    'end_time' => $businessHourData['end_time'],
                    'active' => $businessHourData['active'] ?? true,
                ]);
                $result[] = $this->formatBusinessHour($new);
            }
        }

        return $result;
    }

    /**
     * Formata um horário de funcionamento
     */
    private function formatBusinessHour(TenantBusinessHour $businessHour): array
    {
        return [
            'id' => $businessHour->id,
            'tenant_id' => $businessHour->tenant_id,
            'weekday' => $businessHour->weekday,
            'start_time' => $businessHour->start_time,
            'end_time' => $businessHour->end_time,
            'active' => $businessHour->active,
            'created_at' => $businessHour->created_at?->toISOString(),
            'updated_at' => $businessHour->updated_at?->toISOString(),
        ];
    }
}


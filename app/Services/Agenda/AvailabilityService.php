<?php

namespace App\Services\Agenda;

use App\Models\ProfessionalAvailability;
use App\Models\ProfessionalBlock;
use App\Models\Provider;
use App\Models\Tenant;
use App\Models\TenantBusinessHour;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Verifica se o horário está dentro do horário de funcionamento do tenant
     */
    public function isWithinTenantHours(Tenant $tenant, string $dateStart, string $dateEnd): bool
    {
        $start = Carbon::parse($dateStart);
        $end = Carbon::parse($dateEnd);
        $weekday = $start->dayOfWeek; // 0 = domingo, 6 = sábado

        $businessHour = TenantBusinessHour::where('tenant_id', $tenant->id)
            ->where('weekday', $weekday)
            ->where('active', true)
            ->first();

        if (!$businessHour) {
            return false;
        }

        // Extrai apenas o horário (HH:mm:ss) da data
        $startTime = $start->format('H:i:s');
        $endTime = $end->format('H:i:s');
        $businessStart = $businessHour->start_time;
        $businessEnd = $businessHour->end_time;

        // Verifica se o horário está dentro do horário de funcionamento do tenant
        return $startTime >= $businessStart 
            && $endTime <= $businessEnd;
    }

    /**
     * Verifica se o horário está dentro da disponibilidade do profissional
     * (sempre dentro do horário do tenant)
     */
    public function isWithinAvailability(Provider $provider, string $dateStart, string $dateEnd): bool
    {
        $start = Carbon::parse($dateStart);
        $end = Carbon::parse($dateEnd);
        $weekday = $start->dayOfWeek; // 0 = domingo, 6 = sábado

        $availability = ProfessionalAvailability::where('provider_id', $provider->id)
            ->where('weekday', $weekday)
            ->where('active', true)
            ->first();

        if (!$availability) {
            return false;
        }

        // Extrai apenas o horário (HH:mm:ss) da data
        $startTime = $start->format('H:i:s');
        $endTime = $end->format('H:i:s');
        $availabilityStart = $availability->start_time;
        $availabilityEnd = $availability->end_time;

        // Verifica se o horário está dentro da disponibilidade do dia
        return $startTime >= $availabilityStart 
            && $endTime <= $availabilityEnd;
    }

    /**
     * Verifica se há bloqueio no horário
     */
    public function hasBlock(Provider $provider, string $dateStart, string $dateEnd): bool
    {
        $start = Carbon::parse($dateStart);
        $end = Carbon::parse($dateEnd);

        $blocks = ProfessionalBlock::where('provider_id', $provider->id)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_at', [$start, $end])
                    ->orWhereBetween('end_at', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_at', '<=', $start)
                            ->where('end_at', '>=', $end);
                    });
            })
            ->exists();

        return $blocks;
    }

    /**
     * Obtém todas as disponibilidades de um profissional
     */
    public function getAvailabilities(int $providerId): Collection
    {
        return ProfessionalAvailability::where('provider_id', $providerId)
            ->where('active', true)
            ->orderBy('weekday')
            ->get()
            ->map(function ($availability) {
                return [
                    'id' => $availability->id,
                    'provider_id' => $availability->provider_id,
                    'weekday' => $availability->weekday,
                    'start_time' => $availability->start_time,
                    'end_time' => $availability->end_time,
                    'active' => $availability->active,
                ];
            });
    }

    /**
     * Obtém todos os bloqueios de um profissional no período
     */
    public function getBlocks(int $providerId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = ProfessionalBlock::where('provider_id', $providerId);

        if ($startDate) {
            $query->where('end_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('start_at', '<=', $endDate);
        }

        return $query->orderBy('start_at')
            ->get()
            ->map(function ($block) {
                return [
                    'id' => $block->id,
                    'provider_id' => $block->provider_id,
                    'tenant_id' => $block->tenant_id,
                    'start_at' => $block->start_at->format('Y-m-d\TH:i:s'),
                    'end_at' => $block->end_at->format('Y-m-d\TH:i:s'),
                    'reason' => $block->reason,
                    'created_by' => $block->created_by,
                    'created_at' => $block->created_at?->toISOString(),
                    'updated_at' => $block->updated_at?->toISOString(),
                ];
            });
    }

    /**
     * Cria uma disponibilidade
     */
    public function createAvailability(int $providerId, array $data): array
    {
        $data['provider_id'] = $providerId;
        $availability = ProfessionalAvailability::create($data);

        return [
            'id' => $availability->id,
            'provider_id' => $availability->provider_id,
            'weekday' => $availability->weekday,
            'start_time' => $availability->start_time,
            'end_time' => $availability->end_time,
            'active' => $availability->active,
            'created_at' => $availability->created_at?->toISOString(),
            'updated_at' => $availability->updated_at?->toISOString(),
        ];
    }

    /**
     * Atualiza uma disponibilidade
     */
    public function updateAvailability(int $id, int $providerId, array $data): ?array
    {
        $availability = ProfessionalAvailability::where('id', $id)
            ->where('provider_id', $providerId)
            ->first();

        if (!$availability) {
            return null;
        }

        $availability->update($data);

        return [
            'id' => $availability->id,
            'provider_id' => $availability->provider_id,
            'weekday' => $availability->weekday,
            'start_time' => $availability->start_time,
            'end_time' => $availability->end_time,
            'active' => $availability->active,
            'created_at' => $availability->created_at?->toISOString(),
            'updated_at' => $availability->updated_at?->toISOString(),
        ];
    }

    /**
     * Exclui uma disponibilidade
     */
    public function deleteAvailability(int $id, int $providerId): bool
    {
        $availability = ProfessionalAvailability::where('id', $id)
            ->where('provider_id', $providerId)
            ->first();

        if (!$availability) {
            return false;
        }

        return $availability->delete();
    }

    /**
     * Cria um bloqueio
     */
    public function createBlock(int $providerId, int $tenantId, int $userId, array $data): array
    {
        $data['provider_id'] = $providerId;
        $data['tenant_id'] = $tenantId;
        $data['created_by'] = $userId;

        $block = ProfessionalBlock::create($data);

        return [
            'id' => $block->id,
            'provider_id' => $block->provider_id,
            'tenant_id' => $block->tenant_id,
            'start_at' => $block->start_at->format('Y-m-d\TH:i:s'),
            'end_at' => $block->end_at->format('Y-m-d\TH:i:s'),
            'reason' => $block->reason,
            'created_by' => $block->created_by,
            'created_at' => $block->created_at?->toISOString(),
            'updated_at' => $block->updated_at?->toISOString(),
        ];
    }

    /**
     * Atualiza um bloqueio
     */
    public function updateBlock(int $id, int $providerId, int $tenantId, array $data): ?array
    {
        $block = ProfessionalBlock::where('id', $id)
            ->where('provider_id', $providerId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$block) {
            return null;
        }

        $block->update($data);

        return [
            'id' => $block->id,
            'provider_id' => $block->provider_id,
            'tenant_id' => $block->tenant_id,
            'start_at' => $block->start_at->format('Y-m-d\TH:i:s'),
            'end_at' => $block->end_at->format('Y-m-d\TH:i:s'),
            'reason' => $block->reason,
            'created_by' => $block->created_by,
            'created_at' => $block->created_at?->toISOString(),
            'updated_at' => $block->updated_at?->toISOString(),
        ];
    }

    /**
     * Exclui um bloqueio
     */
    public function deleteBlock(int $id, int $providerId, int $tenantId): bool
    {
        $block = ProfessionalBlock::where('id', $id)
            ->where('provider_id', $providerId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$block) {
            return false;
        }

        return $block->delete();
    }

    /**
     * Cria ou atualiza múltiplas disponibilidades
     */
    public function syncAvailabilities(int $providerId, array $availabilities): array
    {
        $result = [];

        foreach ($availabilities as $availabilityData) {
            $weekday = $availabilityData['weekday'];
            
            $existing = ProfessionalAvailability::where('provider_id', $providerId)
                ->where('weekday', $weekday)
                ->first();

            if ($existing) {
                $existing->update([
                    'start_time' => $availabilityData['start_time'],
                    'end_time' => $availabilityData['end_time'],
                    'active' => $availabilityData['active'] ?? true,
                ]);
                $result[] = [
                    'id' => $existing->id,
                    'provider_id' => $existing->provider_id,
                    'weekday' => $existing->weekday,
                    'start_time' => $existing->start_time,
                    'end_time' => $existing->end_time,
                    'active' => $existing->active,
                ];
            } else {
                $new = ProfessionalAvailability::create([
                    'provider_id' => $providerId,
                    'weekday' => $weekday,
                    'start_time' => $availabilityData['start_time'],
                    'end_time' => $availabilityData['end_time'],
                    'active' => $availabilityData['active'] ?? true,
                ]);
                $result[] = [
                    'id' => $new->id,
                    'provider_id' => $new->provider_id,
                    'weekday' => $new->weekday,
                    'start_time' => $new->start_time,
                    'end_time' => $new->end_time,
                    'active' => $new->active,
                ];
            }
        }

        return $result;
    }
}


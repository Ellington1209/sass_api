<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Provider;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Models\TenantModule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AgendaService
{
    public function createService(?int $tenantId, array $data): array
    {
        return DB::transaction(function () use ($tenantId, $data) {
            $priceData = null;
            if (isset($data['price'])) {
                $priceData = [
                    'price' => $data['price'],
                    'currency' => $data['currency'] ?? 'BRL',
                    'active' => $data['price_active'] ?? true,
                    'start_date' => $data['price_start_date'] ?? null,
                    'end_date' => $data['price_end_date'] ?? null,
                ];
                unset($data['price'], $data['currency'], $data['price_active'], $data['price_start_date'], $data['price_end_date']);
            }

            if ($tenantId !== null) {
                $data['tenant_id'] = $tenantId;
                
                if (isset($data['module_id'])) {
                    $tenantModuleIds = TenantModule::where('tenant_id', $tenantId)
                        ->pluck('module_id')
                        ->toArray();
                    
                    if (!in_array($data['module_id'], $tenantModuleIds)) {
                        throw new \Exception('Módulo não está ativo para este tenant', 422);
                    }
                }
            }
            
            $service = Service::create($data);
            
            if ($priceData && $tenantId !== null) {
                $priceData['tenant_id'] = $tenantId;
                $priceData['service_id'] = $service->id;
                ServicePrice::create($priceData);
            }
            
            $service->load(['module', 'activePrice']);
            return $this->formatService($service);
        });
    }

    public function updateService(int $id, ?int $tenantId, array $data): ?array
    {
        return DB::transaction(function () use ($id, $tenantId, $data) {
            $priceData = null;
            if (isset($data['price'])) {
                $priceData = [
                    'price' => $data['price'],
                    'currency' => $data['currency'] ?? 'BRL',
                    'active' => $data['price_active'] ?? true,
                    'start_date' => $data['price_start_date'] ?? null,
                    'end_date' => $data['price_end_date'] ?? null,
                ];
                unset($data['price'], $data['currency'], $data['price_active'], $data['price_start_date'], $data['price_end_date']);
            }

            $query = Service::where('id', $id);
            
            if ($tenantId !== null) {
                $query->where('tenant_id', $tenantId);
                
                $tenantModuleIds = TenantModule::where('tenant_id', $tenantId)
                    ->pluck('module_id')
                    ->toArray();
                
                if (!empty($tenantModuleIds)) {
                    $query->whereIn('module_id', $tenantModuleIds);
                } else {
                    return null;
                }
                
                if (isset($data['module_id'])) {
                    if (!in_array($data['module_id'], $tenantModuleIds)) {
                        throw new \Exception('Módulo não está ativo para este tenant', 422);
                    }
                }
            }
            
            $service = $query->first();

            if (!$service) {
                return null;
            }

            $updatePrice = $data['update_price'] ?? false;
            unset($data['update_price']);
            
            $service->update($data);
            
            if ($priceData && $tenantId !== null) {
                $priceData['tenant_id'] = $tenantId;
                $priceData['service_id'] = $service->id;
                
                if ($updatePrice === true) {
                    ServicePrice::where('service_id', $service->id)
                        ->where('tenant_id', $tenantId)
                        ->where('active', true)
                        ->update(['active' => false]);
                    
                    if (!isset($priceData['active']) || $priceData['active'] === false) {
                        $priceData['active'] = true;
                    }
                }
                
                ServicePrice::create($priceData);
            }
            
            $service->refresh();
            $service->load(['module', 'activePrice']);
            return $this->formatService($service);
        });
    }

    public function deleteService(int $id, ?int $tenantId): bool
    {
        $query = Service::where('id', $id);
        
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
            
            $tenantModuleIds = TenantModule::where('tenant_id', $tenantId)
                ->pluck('module_id')
                ->toArray();
            
            if (!empty($tenantModuleIds)) {
                $query->whereIn('module_id', $tenantModuleIds);
            } else {
                return false;
            }
        }
        
        $service = $query->first();

        if (!$service) {
            return false;
        }

        return $service->delete();
    }

    public function getAllServices(?int $tenantId, ?array $filters = null): array
    {
        $query = Service::query();
        
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
            
            $tenantModuleIds = TenantModule::where('tenant_id', $tenantId)
                ->pluck('module_id')
                ->toArray();
            
            if (!empty($tenantModuleIds)) {
                $query->whereIn('module_id', $tenantModuleIds);
            } else {
                return [];
            }
        }

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        $services = $query->with(['module', 'activePrice'])->orderBy('name')->get();

        return $services->map(function ($service) {
            return $this->formatService($service);
        })->toArray();
    }

    public function getServiceById(int $id, ?int $tenantId): ?array
    {
        $query = Service::where('id', $id);
        
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }
        
        $service = $query->with(['module', 'activePrice'])->first();

        if (!$service) {
            return null;
        }

        return $this->formatService($service);
    }

    public function createProvider(?int $tenantId, array $data): array
    {
        if ($tenantId !== null) {
            $data['tenant_id'] = $tenantId;
        }
        $provider = Provider::create($data);
        $provider->load('person.user');
        return $this->formatProvider($provider);
    }

    public function updateProvider(int $id, ?int $tenantId, array $data): ?array
    {
        $query = Provider::where('id', $id);
        
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }
        
        $provider = $query->first();

        if (!$provider) {
            return null;
        }

        $provider->update($data);
        $provider->load('person.user');
        return $this->formatProvider($provider);
    }

    public function deleteProvider(int $id, ?int $tenantId): bool
    {
        $query = Provider::where('id', $id);
        
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }
        
        $provider = $query->first();

        if (!$provider) {
            return false;
        }

        return $provider->delete();
    }

    public function getAllProviders(?int $tenantId, ?array $filters = null): array
    {
        $query = Provider::query();
        
        if ($tenantId !== null) {
            $query->where('providers.tenant_id', $tenantId);
        }
        
        // Join para ordenar pelo nome do usuário
        $query->join('persons', 'providers.person_id', '=', 'persons.id')
            ->join('users', 'persons.user_id', '=', 'users.id')
            ->select('providers.*')
            ->orderBy('users.name');
        
        $providers = $query->with('person.user')->get();

        return $providers->map(function ($provider) {
            return $this->formatProvider($provider);
        })->toArray();
    }

    public function getProviderById(int $id, ?int $tenantId): ?array
    {
        $query = Provider::where('id', $id);
        
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }
        
        $provider = $query->with('person.user')->first();

        if (!$provider) {
            return null;
        }

        return $this->formatProvider($provider);
    }

    public function createAppointment(?int $tenantId, array $data): array
    {
        $query = Service::where('id', $data['service_id']);
        
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }
        
        $service = $query->first();

        if (!$service) {
            throw new \Exception('Serviço não encontrado');
        }

        $dateStart = new \DateTime($data['date_start']);
        $dateEnd = (clone $dateStart)->modify("+{$service->duration_minutes} minutes");

        if ($tenantId !== null) {
            $data['tenant_id'] = $tenantId;
        }
        $data['date_end'] = $dateEnd->format('Y-m-d H:i:s');

        $conflicts = $this->checkConflicts(
            $tenantId,
            $data['provider_id'],
            $data['date_start'],
            $data['date_end']
        );

        if (!empty($conflicts)) {
            throw new \Exception('Conflito de horário detectado', 422);
        }

        $appointment = Appointment::create($data);
        $appointment->load(['service', 'provider.person.user', 'client', 'statusAgenda']);

        return $this->formatAppointment($appointment);
    }

    public function updateAppointment(int $id, ?int $tenantId, array $data): ?array
    {
        $query = Appointment::where('id', $id);
        
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }
        
        $appointment = $query->first();

        if (!$appointment) {
            return null;
        }

        if (isset($data['service_id']) || isset($data['date_start'])) {
            $serviceId = $data['service_id'] ?? $appointment->service_id;
            $query = Service::where('id', $serviceId);
            
            if ($tenantId !== null) {
                $query->where('tenant_id', $tenantId);
            }
            
            $service = $query->first();

            if (!$service) {
                throw new \Exception('Serviço não encontrado');
            }

            $dateStart = new \DateTime($data['date_start'] ?? $appointment->date_start);
            $dateEnd = (clone $dateStart)->modify("+{$service->duration_minutes} minutes");
            $data['date_end'] = $dateEnd->format('Y-m-d H:i:s');
        }

        $providerId = $data['provider_id'] ?? $appointment->provider_id;
        $dateStart = $data['date_start'] ?? $appointment->date_start;
        $dateEnd = $data['date_end'] ?? $appointment->date_end;

        $conflicts = $this->checkConflicts(
            $tenantId,
            $providerId,
            $dateStart,
            $dateEnd,
            $id
        );

        if (!empty($conflicts)) {
            throw new \Exception('Conflito de horário detectado', 422);
        }

        $appointment->update($data);
        $appointment->load(['service', 'provider.person.user', 'client', 'statusAgenda']);

        return $this->formatAppointment($appointment);
    }

    public function deleteAppointment(int $id, ?int $tenantId): bool
    {
        $query = Appointment::where('id', $id);
        
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }
        
        $appointment = $query->first();

        if (!$appointment) {
            return false;
        }

        return $appointment->delete();
    }

    public function getAllAppointments(?int $tenantId, ?array $filters = null): array
    {
        $query = Appointment::query();
        
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }
        
        $query->with(['service', 'provider.person.user', 'client', 'statusAgenda']);

        if (isset($filters['provider_id'])) {
            $query->where('provider_id', $filters['provider_id']);
        }

        if (isset($filters['date_start'])) {
            $query->where('date_start', '>=', $filters['date_start']);
        }

        if (isset($filters['date_end'])) {
            $query->where('date_end', '<=', $filters['date_end']);
        }

        $appointments = $query->orderBy('date_start')->get();

        return $appointments->map(function ($appointment) {
            return $this->formatAppointment($appointment);
        })->toArray();
    }

    public function getAppointmentById(int $id, ?int $tenantId): ?array
    {
        $query = Appointment::where('id', $id);
        
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }
        
        $appointment = $query->with(['service', 'provider.user', 'client', 'statusAgenda'])->first();

        if (!$appointment) {
            return null;
        }

        return $this->formatAppointment($appointment);
    }

    public function checkConflicts(int $tenantId, int $providerId, string $dateStart, string $dateEnd, ?int $excludeId = null): array
    {
        $query = Appointment::where('tenant_id', $tenantId)
            ->where('provider_id', $providerId)
            ->where(function ($q) use ($dateStart, $dateEnd) {
                $q->whereBetween('date_start', [$dateStart, $dateEnd])
                    ->orWhereBetween('date_end', [$dateStart, $dateEnd])
                    ->orWhere(function ($q2) use ($dateStart, $dateEnd) {
                        $q2->where('date_start', '<=', $dateStart)
                            ->where('date_end', '>=', $dateEnd);
                    });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get()->toArray();
    }

    private function formatService(Service $service): array
    {
        return [
            'id' => $service->id,
            'tenant_id' => $service->tenant_id,
            'module_id' => $service->module_id,
            'name' => $service->name,
            'slug' => $service->slug,
            'duration_minutes' => $service->duration_minutes,
            'active' => $service->active,
            'module' => $service->module ? [
                'id' => $service->module->id,
                'key' => $service->module->key,
                'name' => $service->module->name,
            ] : null,
            'price' => $service->activePrice ? [
                'id' => $service->activePrice->id,
                'price' => (float) $service->activePrice->price,
                'currency' => $service->activePrice->currency,
                'start_date' => $service->activePrice->start_date?->format('Y-m-d'),
                'end_date' => $service->activePrice->end_date?->format('Y-m-d'),
            ] : null,
            'created_at' => $service->created_at?->toISOString(),
            'updated_at' => $service->updated_at?->toISOString(),
        ];
    }

    private function formatProvider(Provider $provider): array
    {
        $person = $provider->person;
        $user = $person?->user;

        return [
            'id' => $provider->id,
            'tenant_id' => $provider->tenant_id,
            'person_id' => $provider->person_id,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
            'person' => $person ? [
                'id' => $person->id,
                'cpf' => $person->cpf,
                'rg' => $person->rg,
                'birth_date' => $person->birth_date?->format('Y-m-d'),
                'phone' => $person->phone,
                'address' => [
                    'street' => $person->address_street,
                    'number' => $person->address_number,
                    'complement' => $person->address_complement,
                    'neighborhood' => $person->address_neighborhood,
                    'city' => $person->address_city,
                    'state' => $person->address_state,
                    'zip' => $person->address_zip,
                ],
            ] : null,
            'photo_url' => $person?->photo_url ? (
                str_starts_with($person->photo_url, 'tenants/') 
                    ? url('/api/files/public/' . urlencode($person->photo_url))
                    : $person->photo_url
            ) : null,
            'service_ids' => $provider->service_ids,
            'services' => $this->getServicesData($provider->service_ids),
            'created_at' => $provider->created_at?->toISOString(),
            'updated_at' => $provider->updated_at?->toISOString(),
        ];
    }

    /**
     * Busca os dados dos serviços pelos IDs
     */
    private function getServicesData(?array $serviceIds): array
    {
        if (!$serviceIds || empty($serviceIds)) {
            return [];
        }

        // Converte strings para inteiros se necessário
        $ids = array_map('intval', $serviceIds);
        
        $services = \App\Models\Service::whereIn('id', $ids)
            ->select('id', 'name', 'slug')
            ->get();

        return $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'slug' => $service->slug,
            ];
        })->toArray();
    }

    private function formatAppointment(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'tenant_id' => $appointment->tenant_id,
            'service_id' => $appointment->service_id,
            'provider_id' => $appointment->provider_id,
            'client_id' => $appointment->client_id,
            'date_start' => $appointment->date_start?->toISOString(),
            'date_end' => $appointment->date_end?->toISOString(),
            'status_agenda_id' => $appointment->status_agenda_id,
            'notes' => $appointment->notes,
            'service' => $appointment->service ? [
                'id' => $appointment->service->id,
                'name' => $appointment->service->name,
                'slug' => $appointment->service->slug,
                'duration_minutes' => $appointment->service->duration_minutes,
            ] : null,
            'provider' => $appointment->provider ? [
                'id' => $appointment->provider->id,
                'person_id' => $appointment->provider->person_id,
                'user' => $appointment->provider->person?->user ? [
                    'id' => $appointment->provider->person->user->id,
                    'name' => $appointment->provider->person->user->name,
                    'email' => $appointment->provider->person->user->email,
                ] : null,
                'person' => $appointment->provider->person ? [
                    'id' => $appointment->provider->person->id,
                    'cpf' => $appointment->provider->person->cpf,
                    'rg' => $appointment->provider->person->rg,
                    'birth_date' => $appointment->provider->person->birth_date?->format('Y-m-d'),
                    'phone' => $appointment->provider->person->phone,
                ] : null,
                'service_ids' => $appointment->provider->service_ids,
            ] : null,
            'client' => $appointment->client ? [
                'id' => $appointment->client->id,
                'name' => $appointment->client->name,
                'email' => $appointment->client->email,
            ] : null,
            'status_agenda' => $appointment->statusAgenda ? [
                'id' => $appointment->statusAgenda->id,
                'key' => $appointment->statusAgenda->key,
                'name' => $appointment->statusAgenda->name,
            ] : null,
            'created_at' => $appointment->created_at?->toISOString(),
            'updated_at' => $appointment->updated_at?->toISOString(),
        ];
    }
}


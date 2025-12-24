<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'service_id' => $this->service_id,
            'provider_id' => $this->provider_id,
            'client_id' => $this->client_id,
            'date_start' => $this->date_start?->format('Y-m-d\TH:i:s'),
            'date_end' => $this->date_end?->format('Y-m-d\TH:i:s'),
            'status_agenda_id' => $this->status_agenda_id,
            'notes' => $this->notes,
            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                    'slug' => $this->service->slug,
                    'duration_minutes' => $this->service->duration_minutes,
                ];
            }),
            'provider' => $this->whenLoaded('provider', function () {
                return [
                    'id' => $this->provider->id,
                    'name' => $this->provider->name,
                    'user' => $this->provider->user ? [
                        'id' => $this->provider->user->id,
                        'name' => $this->provider->user->name,
                        'email' => $this->provider->user->email,
                    ] : null,
                ];
            }),
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'email' => $this->client->email,
                ];
            }),
            'status_agenda' => $this->whenLoaded('statusAgenda', function () {
                return [
                    'id' => $this->statusAgenda->id,
                    'key' => $this->statusAgenda->key,
                    'name' => $this->statusAgenda->name,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}


<?php

namespace App\Models\Financial;

use App\Models\Provider;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderCommissionConfig extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'provider_id',
        'service_id',
        'commission_rate',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Scope para filtrar configs ativas
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope para buscar config por provider e service
     * Hierarquia de prioridade:
     * 1. provider + service (mais específica)
     * 2. provider apenas (padrão)
     */
    public function scopeForProviderService($query, int $providerId, ?int $serviceId = null)
    {
        return $query->where('provider_id', $providerId)
            ->where('active', true)
            ->where(function($q) use ($serviceId) {
                if ($serviceId) {
                    // Busca config específica do service ou padrão
                    $q->where('service_id', $serviceId)
                      ->orWhereNull('service_id');
                } else {
                    // Busca apenas config padrão
                    $q->whereNull('service_id');
                }
            })
            // Ordena por especificidade: mais específica primeiro
            ->orderByRaw('CASE WHEN service_id IS NOT NULL THEN 1 ELSE 2 END ASC');
    }
}


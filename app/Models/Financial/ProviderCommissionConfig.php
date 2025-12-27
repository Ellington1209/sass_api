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
        'origin_id',
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

    public function origin(): BelongsTo
    {
        return $this->belongsTo(FinancialOrigin::class, 'origin_id');
    }

    /**
     * Scope para filtrar configs ativas
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope para buscar config por provider, service e origin
     * Hierarquia de prioridade:
     * 1. provider + service + origin (mais específica)
     * 2. provider + service (sem origin)
     * 3. provider + origin (sem service)
     * 4. provider apenas (padrão)
     */
    public function scopeForProviderServiceAndOrigin($query, int $providerId, ?int $serviceId = null, ?int $originId = null)
    {
        return $query->where('provider_id', $providerId)
            ->where('active', true)
            ->where(function($q) use ($serviceId, $originId) {
                // Busca configs que se aplicam (service_id match ou NULL, origin_id match ou NULL)
                $q->where(function($subQ) use ($serviceId, $originId) {
                    // Config específica: service + origin
                    if ($serviceId && $originId) {
                        $subQ->where(function($s) use ($serviceId, $originId) {
                            $s->where('service_id', $serviceId)->where('origin_id', $originId);
                        });
                    }
                    // Config por service apenas
                    if ($serviceId) {
                        $subQ->orWhere(function($s) use ($serviceId) {
                            $s->where('service_id', $serviceId)->whereNull('origin_id');
                        });
                    }
                    // Config por origin apenas
                    if ($originId) {
                        $subQ->orWhere(function($s) use ($originId) {
                            $s->whereNull('service_id')->where('origin_id', $originId);
                        });
                    }
                    // Config padrão (sem service e sem origin)
                    $subQ->orWhere(function($s) {
                        $s->whereNull('service_id')->whereNull('origin_id');
                    });
                });
            })
            // Ordena por especificidade: mais específica primeiro
            ->orderByRaw('
                CASE 
                    WHEN service_id IS NOT NULL AND origin_id IS NOT NULL THEN 1
                    WHEN service_id IS NOT NULL THEN 2
                    WHEN origin_id IS NOT NULL THEN 3
                    ELSE 4
                END ASC
            ');
    }
}


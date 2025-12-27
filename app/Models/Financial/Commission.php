<?php

namespace App\Models\Financial;

use App\Models\Provider;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Commission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'provider_id',
        'transaction_id',
        'reference_type',
        'reference_id',
        'base_amount',
        'commission_amount',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'paid_at' => 'datetime',
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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class, 'transaction_id');
    }

    /**
     * Accessor para formatar o status de forma legível
     */
    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            'PENDING' => 'Pendente',
            'PAID' => 'Pago',
            'CANCELLED' => 'Cancelado',
            default => $this->status,
        };
    }

    /**
     * Scope para filtrar por status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para filtrar comissões pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    /**
     * Scope para filtrar comissões pagas
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'PAID');
    }

    /**
     * Scope para filtrar por provider
     */
    public function scopeByProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }
}


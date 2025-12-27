<?php

namespace App\Models\Financial;

use App\Models\ServicePrice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'type',
        'amount',
        'description',
        'origin_id',
        'category_id',
        'payment_method_id',
        'reference_type',
        'reference_id',
        'service_price_id',
        'status',
        'occurred_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(FinancialOrigin::class, 'origin_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'category_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function servicePrice(): BelongsTo
    {
        return $this->belongsTo(ServicePrice::class, 'service_price_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'transaction_id');
    }

    public function commissionsPayments(): HasMany
    {
        return $this->hasMany(Commission::class, 'payment_transaction_id');
    }

    /**
     * Accessor para formatar o tipo de forma legível
     */
    public function getTypeNameAttribute(): string
    {
        return $this->type === 'IN' ? 'Entrada' : 'Saída';
    }

    /**
     * Accessor para formatar o status de forma legível
     */
    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            'PENDING' => 'Pendente',
            'CONFIRMED' => 'Confirmado',
            'CANCELLED' => 'Cancelado',
            default => $this->status,
        };
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'IN');
    }

    /**
     * Scope para filtrar por saídas
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'OUT');
    }

    /**
     * Scope para filtrar por status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para filtrar por período
     */
    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }
}


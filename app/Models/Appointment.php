<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'service_id',
        'provider_id',
        'client_id',
        'date_start',
        'date_end',
        'status_agenda_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_start' => 'datetime',
            'date_end' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function statusAgenda(): BelongsTo
    {
        return $this->belongsTo(StatusAgenda::class, 'status_agenda_id');
    }
}


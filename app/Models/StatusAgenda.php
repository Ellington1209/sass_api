<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatusAgenda extends Model
{
    use HasFactory;

    protected $table = 'status_agenda';

    protected $fillable = [
        'key',
        'name',
        'description',
        'order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'status_agenda_id');
    }
}


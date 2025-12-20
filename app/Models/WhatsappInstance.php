<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'evolution_id',
        'tenant_id',
        'name',
        'status',
        'owner_jid',
    ];


    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}


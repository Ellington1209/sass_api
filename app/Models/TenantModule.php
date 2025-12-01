<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantModule extends Model
{
    protected $table = 'tenant_modules';
    
    protected $fillable = [
        'tenant_id',
        'module_id',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}


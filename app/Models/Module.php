<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function tenantModules()
    {
        return $this->hasMany(TenantModule::class);
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'tenant_modules');
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}

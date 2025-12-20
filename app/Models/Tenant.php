<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }


    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function tenantUsers()
    {
        return $this->hasMany(TenantUser::class);
    }

    public function tenantModules()
    {
        return $this->hasMany(TenantModule::class);
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'tenant_modules');
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function whatsappInstances()
    {
        return $this->hasMany(WhatsappInstance::class);
    }
}


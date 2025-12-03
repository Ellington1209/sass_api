<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class);
    }

    public function tenantUsers()
    {
        return $this->hasMany(TenantUser::class);
    }

    public function isTenantAdmin(): bool
    {
        return $this->tenantUsers()->exists();
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }
}


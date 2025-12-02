<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantUser extends Model
{
    protected $table = 'tenant_users';
    protected $fillable = [
        'tenant_id',
        'user_id',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

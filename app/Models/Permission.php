<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'permission_key',
        'module_id',
        'descricao',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'active',
        'active_modules',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'active_modules' => 'array',
        ];
    }


    public function users()
    {
        return $this->hasMany(User::class);
    }
}


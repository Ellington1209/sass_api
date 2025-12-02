<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasTenantScope
{
    protected static function bootHasTenantScope()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (!Auth::check()) {
                return;
            }

            $user = Auth::user();

            if (!$user) {
                return;
            }

            // Super-admin vê tudo
            if ($user->is_super_admin) {
                return;
            }

            // Se tiver tenant_id, filtra por tenant
            if ($user->tenant_id) {
                $builder->where('tenant_id', $user->tenant_id);
            }
        });
    }
}


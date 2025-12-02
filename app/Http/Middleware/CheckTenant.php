<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Super-admin ignora tenant
        if ($user->is_super_admin) {
            return $next($request);
        }

        // Funcionário do super-admin (tenant_id null) - pode acessar
        if ($user->tenant_id === null) {
            return $next($request);
        }

        // Usuário normal - precisa ter tenant ativo
        if ($user->tenant_id) {
            $user->load('tenant');
            $tenant = $user->tenant;
            if (!$tenant || !$tenant->active) {
                return response()->json([
                    'message' => 'Tenant is not active',
                ], 403);
            }
        }

        return $next($request);
    }
}


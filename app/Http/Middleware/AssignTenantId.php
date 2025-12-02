<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssignTenantId
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Só funciona para métodos que modificam dados
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $response;
        }

        // Se não estiver autenticado, não faz nada
        if (!auth()->check()) {
            return $response;
        }

        $user = auth()->user();

        // Super-admin não precisa de tenant_id automático
        if ($user->is_super_admin) {
            return $response;
        }

        // Se o usuário tiver tenant_id, injeta automaticamente nos dados da requisição
        if ($user->tenant_id && $request->has('tenant_id') === false) {
            $request->merge(['tenant_id' => $user->tenant_id]);
        }

        return $response;
    }
}


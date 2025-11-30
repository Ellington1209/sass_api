<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Super-admin ignora permissões
        if ($user->is_super_admin) {
            return $next($request);
        }

        // Carrega as permissões do usuário
        $user->load('userPermissions');
        $userPermissions = $user->userPermissions->pluck('permission_key')->toArray();

        if (!in_array($permission, $userPermissions)) {
            return response()->json([
                'message' => 'You do not have permission to access this resource',
            ], 403);
        }

        return $next($request);
    }
}


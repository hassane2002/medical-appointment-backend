<?php
// app/Http/Middleware/CheckRole.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        $user = $request->user();
        $user->load('role');

        $userRole = $user->role->name;

        if (!in_array($userRole, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé pour ce rôle',
                'user_role' => $userRole,
                'required_roles' => $roles
            ], 403);
        }

        return $next($request);
    }
}
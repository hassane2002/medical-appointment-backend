<?php

// app/Http/Middleware/CheckPatient.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPatient
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isPatient()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux patients'
            ], 403);
        }

        return $next($request);
    }
}
<?php

// app/Http/Middleware/CheckDoctor.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckDoctor
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isDoctor()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux médecins'
            ], 403);
        }

        return $next($request);
    }
}
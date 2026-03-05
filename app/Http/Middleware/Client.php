<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Client
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si non connecté → login
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        // Si connecté mais pas client → accès refusé
        if (auth()->user()->role !== 'client') {
            abort(403, 'Accès réservé aux clients');
        }

        return $next($request);
    }
}

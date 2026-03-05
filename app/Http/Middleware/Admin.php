<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Admin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifie que l'utilisateur est connecté
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        // Vérifie que l'utilisateur est ADMIN
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Accès réservé à l\'administrateur');
        }

        return $next($request);
    }
}

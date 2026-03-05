<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Vendeur
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est authentifié
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Vous devez être connecté pour accéder à cette page.');
        }

        // Vérifier si l'utilisateur a le rôle vendeur
        if (Auth::user()->role !== 'vendeur') {
            abort(403, 'Accès réservé aux vendeurs.');
        }

        // Pages autorisées sans boutique
        $allowedRoutes = [
            'vendeur.profile.create',
            'vendeur.profile.store',
            'vendeur.profile.index',
            'vendeur.profile.edit',
            'vendeur.profile.update',
            'vendeur.profile.password.edit',
            'vendeur.profile.password.update',
            'vendeur.profile.delete-logo',
            'vendeur.profile.info',
        ];
        
        // Vérifier si la route actuelle est autorisée
        $currentRoute = $request->route()->getName();
        if (in_array($currentRoute, $allowedRoutes)) {
            return $next($request);
        }

        // Vérifier si le vendeur a une boutique
        $vendor = Auth::user()->vendor;
        if (!$vendor) {
            return redirect()->route('vendeur.profile.create')
                ->with('info', 'Vous devez d\'abord créer votre boutique.');
        }

        if ($vendor->status !== 'approved') {
            return redirect()->route('vendeur.profile.index')
                ->with('error', 'Votre boutique n\'est pas encore validée.');
        }

        return $next($request);
    }
}

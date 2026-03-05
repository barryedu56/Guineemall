<?php

namespace App\Http\Middleware;

use App\Models\AdminLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActions
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Ne logger que les routes admin
        if ($request->user() && $request->user()->role === 'admin') {
            // Exclure certaines routes (GET dashboard, GET logs, etc.)
            $excludedRoutes = [
                'admin.dashboard',
                'admin.logs.index',
                'admin.logs.show',
            ];

            $routeName = $request->route()->getName();
            
            if (!in_array($routeName, $excludedRoutes)) {
                $this->logAction($request, $routeName);
            }
        }

        return $response;
    }

    /**
     * Enregistrer l'action admin
     */
    private function logAction(Request $request, string $routeName): void
    {
        $action = $this->getActionDescription($request, $routeName);

        AdminLog::create([
            'admin_id' => $request->user()->id,
            'action' => $action,
        ]);
    }

    /**
     * Générer une description compréhensible de l'action
     */
    private function getActionDescription(Request $request, string $routeName): string
    {
        $method = $request->method();
        $resource = $this->getResourceName($routeName);
        $action = $this->getActionVerb($method, $routeName);

        // Récupérer l'ID de la ressource si disponible
        $id = $request->route('id') ?? $request->route('vendor') ?? 
              $request->route('client') ?? $request->route('category') ?? 
              $request->route('product') ?? $request->route('order');

        $description = "{$action} {$resource}";
        
        if ($id) {
            $description .= " #{$id}";
        }

        // Ajouter des détails simplifiés pour certaines actions (limiter la longueur)
        if ($request->isMethod('POST') || $request->isMethod('PUT')) {
            $data = $request->except(['_token', '_method', 'password', 'password_confirmation']);
            if (!empty($data)) {
                // Limiter à quelques champs clés pour éviter les logs trop longs
                $keys = array_keys($data);
                $maxKeys = 3; // Limiter à 3 clés maximum
                if (count($keys) > $maxKeys) {
                    $keys = array_slice($keys, 0, $maxKeys);
                    $keys[] = '...'; // Indiquer qu'il y a plus de champs
                }
                $description .= " - [" . implode(', ', $keys) . "]";
            }
        }

        return $description;
    }

    /**
     * Obtenir le nom de la ressource à partir du nom de la route
     */
    private function getResourceName(string $routeName): string
    {
        $resourceMap = [
            'vendors' => 'vendeur',
            'clients' => 'client',
            'products' => 'produit',
            'categories' => 'catégorie',
            'orders' => 'commande',
            'logs' => 'log',
        ];

        foreach ($resourceMap as $key => $resource) {
            if (str_contains($routeName, $key)) {
                return $resource;
            }
        }

        return 'ressource';
    }

    /**
     * Obtenir le verbe d'action selon la méthode HTTP et la route
     */
    private function getActionVerb(string $method, string $routeName): string
    {
        if ($method === 'GET' && str_contains($routeName, '.index')) {
            return 'Consultation';
        }
        
        if ($method === 'GET' && str_contains($routeName, '.show')) {
            return 'Affichage';
        }
        
        if ($method === 'GET' && str_contains($routeName, '.create')) {
            return 'Création';
        }
        
        if ($method === 'GET' && str_contains($routeName, '.edit')) {
            return 'Modification';
        }
        
        if ($method === 'POST') {
            return 'Création';
        }
        
        if ($method === 'PUT' || $method === 'PATCH') {
            if (str_contains($routeName, '.approve')) {
                return 'Approbation';
            }
            if (str_contains($routeName, '.suspend')) {
                return 'Suspension';
            }
            if (str_contains($routeName, '.block')) {
                return 'Blocage';
            }
            if (str_contains($routeName, '.activate')) {
                return 'Activation';
            }
            return 'Mise à jour';
        }
        
        if ($method === 'DELETE') {
            return 'Suppression';
        }

        return 'Action';
    }
}

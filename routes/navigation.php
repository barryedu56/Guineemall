<?php

/*
|--------------------------------------------------------------------------
| Routes de Navigation et Debug
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;

// Page de test pour vérifier toutes les routes
Route::get('/test-routes', function () {
    $routes = [
        '🏠 Page d\'accueil' => route('home'),
        '🔐 Login' => route('login'),
        '📝 Register' => route('register'),
        '🚪 Logout' => route('logout'),
        '👤 Dashboard général' => route('dashboard'),
    ];
    
    if (auth()->check()) {
        $user = auth()->user();
        
        if ($user->role === 'admin') {
            $routes['👨‍💼 Admin Dashboard'] = route('admin.dashboard');
            $routes['📊 Admin Vendors'] = route('admin.vendors.index');
            $routes['📦 Admin Categories'] = route('admin.categories.index');
            $routes['🛍️ Admin Products'] = route('admin.products.index');
            $routes['📋 Admin Orders'] = route('admin.orders.index');
        }
        
        if ($user->role === 'vendeur') {
            $routes['🏪 Vendeur Dashboard'] = route('vendeur.dashboard');
            $routes['📦 Vendeur Products'] = route('vendeur.products.index');
            $routes['➕ Vendeur Add Product'] = route('vendeur.products.create');
            $routes['📋 Vendeur Orders'] = route('vendeur.orders.index');
            $routes['👤 Vendeur Profile'] = route('vendeur.profile.index');
        }
        
        if ($user->role === 'client') {
            $routes['🛒 Client Dashboard'] = route('client.dashboard');
            $routes['📦 Client Catalog'] = route('client.catalog.index');
            $routes['🛍️ Client Cart'] = route('client.cart.index');
            $routes['📋 Client Orders'] = route('client.orders.index');
            $routes['👤 Client Profile'] = route('client.profile.index');
        }
    }
    
    return response()->json([
        'user' => auth()->check() ? [
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'role' => auth()->user()->role,
        ] : null,
        'routes' => $routes,
        'message' => 'Routes disponibles pour navigation'
    ]);
});

// Route de debug pour middleware
Route::get('/debug-middleware', function () {
    $info = [
        'auth_check' => auth()->check(),
        'user' => auth()->check() ? [
            'id' => auth()->user()->id,
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'role' => auth()->user()->role,
        ] : null,
        'session' => session()->all(),
        'middleware_info' => [
            'admin_middleware' => class_exists('App\Http\Middleware\Admin'),
            'client_middleware' => class_exists('App\Http\Middleware\Client'),
            'vendeur_middleware' => class_exists('App\Http\Middleware\Vendeur'),
        ]
    ];
    
    return response()->json($info);
});

// Route pour créer rapidement des utilisateurs de test
Route::get('/create-test-users', function () {
    try {
        // Admin
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@guineemall.com'],
            [
                'name' => 'Administrateur',
                'password' => \Hash::make('admin123'),
                'role' => 'admin',
            ]
        );
        
        // Vendeur
        $vendeur = \App\Models\User::firstOrCreate(
            ['email' => 'vendeur@guineemall.com'],
            [
                'name' => 'Vendeur Test',
                'password' => \Hash::make('vendeur123'),
                'role' => 'vendeur',
            ]
        );
        
        // Client
        $client = \App\Models\User::firstOrCreate(
            ['email' => 'client@guineemall.com'],
            [
                'name' => 'Client Test',
                'password' => \Hash::make('client123'),
                'role' => 'client',
            ]
        );
        
        return response()->json([
            'success' => true,
            'users' => [
                'admin' => ['email' => $admin->email, 'password' => 'admin123'],
                'vendeur' => ['email' => $vendeur->email, 'password' => 'vendeur123'],
                'client' => ['email' => $client->email, 'password' => 'client123'],
            ],
            'message' => 'Utilisateurs de test créés avec succès!'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

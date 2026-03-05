<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Vendeur\DashboardController;
use App\Http\Controllers\Vendeur\ProductController;
use App\Http\Controllers\Vendeur\OrderController;
use App\Http\Controllers\Vendeur\ProfileController;

/*
|--------------------------------------------------------------------------
| Routes VENDEUR
|--------------------------------------------------------------------------
|
| Routes pour l'interface vendeur de GuinéeMall
| Toutes les routes nécessitent une authentification et le rôle "vendeur"
|
*/

Route::middleware(['auth', 'vendeur'])
    ->prefix('vendeur')
    ->name('vendeur.')
    ->group(function () {

    /*
    |-------------------------------------------------
    | Tableau de bord vendeur
    |-------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])
        ->name('dashboard.stats');

    /*
    |-------------------------------------------------
    | Boutique vendeur
    |-------------------------------------------------
    */
    Route::get('/shop', [ProfileController::class, 'show'])
        ->name('shop.show');

    /*
    |-------------------------------------------------
    | Gestion des produits
    |-------------------------------------------------
    */
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])
            ->name('index');
            
        Route::get('/create', [ProductController::class, 'create'])
            ->name('create');
            
        Route::post('/', [ProductController::class, 'store'])
            ->middleware('throttle:product-create')
            ->name('store');
            
        Route::get('/{product}', [ProductController::class, 'show'])
            ->name('show');
            
        Route::get('/{product}/edit', [ProductController::class, 'edit'])
            ->name('edit');
            
        Route::patch('/{product}', [ProductController::class, 'update'])
            ->name('update');
            
        Route::delete('/{product}', [ProductController::class, 'destroy'])
            ->name('destroy');
            
        Route::patch('/{product}/toggle-status', [ProductController::class, 'toggleStatus'])
            ->name('toggle-status');
            
        Route::patch('/{product}/stock', [ProductController::class, 'updateStock'])
            ->name('update-stock');
    });

    /*
    |-------------------------------------------------
    | Gestion des notifications
    |-------------------------------------------------
    */
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', function() {
            return view('vendeur.notifications.index');
        })->name('index');
    });

    /*
    |-------------------------------------------------
    | Gestion des commandes
    |-------------------------------------------------
    */
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])
            ->name('index');

        Route::get('/export', [OrderController::class, 'export'])
            ->name('export');

        Route::get('/stats', [OrderController::class, 'stats'])
            ->name('stats');

        Route::get('/sales-report', [OrderController::class, 'salesReport'])
            ->name('sales-report');
            
        Route::get('/{order}', [OrderController::class, 'show'])
            ->whereNumber('order')
            ->name('show');
            
        Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])
            ->whereNumber('order')
            ->name('update-status');
            
        Route::match(['patch', 'post'], '/{order}/confirm', [OrderController::class, 'confirm'])
            ->whereNumber('order')
            ->name('confirm');
            
        Route::match(['patch', 'post'], '/{order}/preparing', [OrderController::class, 'preparing'])
            ->whereNumber('order')
            ->name('preparing');
            
        Route::match(['patch', 'post'], '/{order}/ready', [OrderController::class, 'ready'])
            ->whereNumber('order')
            ->name('ready');
            
        Route::match(['patch', 'post'], '/{order}/delivered', [OrderController::class, 'delivered'])
            ->whereNumber('order')
            ->name('delivered');
            
        Route::delete('/{order}/cancel', [OrderController::class, 'cancel'])
            ->whereNumber('order')
            ->name('cancel');
    });

    /*
    |-------------------------------------------------
    | Gestion du profil vendeur
    |-------------------------------------------------
    */
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])
            ->name('index');
            
        Route::get('/create', [ProfileController::class, 'create'])
            ->name('create');
            
        Route::post('/', [ProfileController::class, 'store'])
            ->name('store');
            
        Route::get('/edit', [ProfileController::class, 'edit'])
            ->name('edit');
            
        Route::patch('/', [ProfileController::class, 'update'])
            ->name('update');
            
        Route::get('/password', [ProfileController::class, 'editPassword'])
            ->name('password.edit');
            
        Route::match(['post', 'put'], '/password', [ProfileController::class, 'updatePassword'])
            ->name('password.update');
            
        Route::delete('/logo', [ProfileController::class, 'deleteLogo'])
            ->name('delete-logo');
            
        Route::get('/info', [ProfileController::class, 'info'])
            ->name('info');
    });
});

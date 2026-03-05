<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\TransparentController;

// ADMIN controllers
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| Routes publiques
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/up', function () {
    $checks = [
        'database' => false,
        'cache' => false,
        'queue' => false,
    ];

    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (\Throwable $e) {
        $checks['database'] = false;
    }

    try {
        Cache::store()->put('healthcheck', 'ok', 5);
        $checks['cache'] = Cache::store()->get('healthcheck') === 'ok';
    } catch (\Throwable $e) {
        $checks['cache'] = false;
    }

    try {
        Queue::size();
        $checks['queue'] = true;
    } catch (\Throwable $e) {
        $checks['queue'] = false;
    }

    $healthy = collect($checks)->every(fn ($value) => $value === true);

    return response()->json([
        'status' => $healthy ? 'ok' : 'degraded',
        'checks' => $checks,
    ], $healthy ? 200 : 500);
});

/*
|--------------------------------------------------------------------------
| Pages publiques d'information
|--------------------------------------------------------------------------
*/
Route::view('/about', 'client.pages.about')->name('pages.about');
Route::view('/how-it-works', 'client.pages.how')->name('pages.how');
Route::view('/become-vendor', 'client.pages.become-vendor')->name('pages.become-vendor');
Route::view('/contact', 'client.pages.contact')->name('pages.contact');
Route::view('/faq', 'client.pages.faq')->name('pages.faq');
Route::view('/delivery', 'client.pages.delivery')->name('pages.delivery');

// Pages statiques avec contrôleur transparent
Route::get('/pages/vendors', [PagesController::class, 'vendors'])->name('pages.vendors');
Route::get('/pages/terms', [TransparentController::class, 'terms'])->name('pages.terms');
Route::get('/pages/privacy', [TransparentController::class, 'privacy'])->name('pages.privacy');
Route::get('/pages/cookies', [TransparentController::class, 'cookies'])->name('pages.cookies');
Route::get('/pages/legal', [TransparentController::class, 'legal'])->name('pages.legal');

// Pages fonctionnalités cliquables
Route::get('/pages/secure-payments', [PagesController::class, 'securePayments'])->name('pages.secure-payments');
Route::get('/pages/delivery', [PagesController::class, 'delivery'])->name('pages.delivery-service');
Route::get('/pages/support', [PagesController::class, 'support'])->name('pages.support');
Route::get('/pages/best-prices', [PagesController::class, 'bestPrices'])->name('pages.best-prices');
Route::get('/pages/verified-vendors', [PagesController::class, 'verifiedVendors'])->name('pages.verified-vendors');
Route::get('/pages/returns', [PagesController::class, 'returns'])->name('pages.returns');

Route::view('/how-to-buy', 'pages.how-to-buy')->name('pages.how_to_buy');
Route::view('/payment-methods', 'pages.payment-methods')->name('pages.payment_methods');
Route::view('/pricing-commissions', 'pages.pricing-commissions')->name('pages.pricing_commissions');
Route::view('/seller-guide', 'pages.seller-guide')->name('pages.seller_guide');
Route::view('/success-stories', 'pages.success-stories')->name('pages.success_stories');
Route::view('/affiliation', 'pages.affiliation')->name('pages.affiliation');

// Routes de debug et navigation
require __DIR__.'/navigation.php';

// Route de debug pour les catégories
require __DIR__.'/debug.php';

/*
|--------------------------------------------------------------------------
| Redirection intelligente après connexion
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    $user = auth()->user();

    return match ($user->role) {
        'admin'   => redirect()->route('admin.dashboard'),
        'client'  => redirect()->route('client.dashboard'),
        'vendeur' => redirect()->route('vendeur.dashboard'),
        default   => abort(403),
    };
})->middleware('auth')->name('dashboard');

/*
|--------------------------------------------------------------------------
| Routes AUTH - Utilisateurs authentifiés
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Les routes génériques de profil ont été supprimées
    // Utiliser les routes spécifiques : admin.profile, client.profile, vendeur.profile
});

/*
|--------------------------------------------------------------------------
| Routes ADMIN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin', 'admin.logs'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        /*
        | Dashboard
        */
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        /*
        | Profil administrateur
        */
        Route::get('/profile', [\App\Http\Controllers\Admin\AdminProfileController::class, 'edit'])
            ->name('profile.edit');

        Route::patch('/profile', [\App\Http\Controllers\Admin\AdminProfileController::class, 'update'])
            ->name('profile.update');

        Route::patch('/password', [\App\Http\Controllers\Admin\AdminProfileController::class, 'updatePassword'])
            ->name('password.update');

        Route::delete('/profile', [\App\Http\Controllers\Admin\AdminProfileController::class, 'destroy'])
            ->name('profile.destroy');

        /*
        | Messages administrateur
        */
        Route::get('/messages', [\App\Http\Controllers\Admin\MessageController::class, 'index'])
            ->name('messages.index');
        
        Route::get('/messages/{user}', [\App\Http\Controllers\Admin\MessageController::class, 'show'])
            ->name('messages.show');
        
        Route::post('/messages/send/{user}', [\App\Http\Controllers\Admin\MessageController::class, 'send'])
            ->name('messages.send');

        /*
        | Gestion des vendeurs
        */
        Route::get('/vendors', [VendorController::class, 'index'])
            ->name('vendors.index');

        Route::get('/vendors/create', [VendorController::class, 'create'])
            ->name('vendors.create');

        Route::post('/vendors', [VendorController::class, 'store'])
            ->name('vendors.store');

        Route::get('/vendors/{vendor}', [VendorController::class, 'show'])
            ->name('vendors.show');

        Route::patch('/vendors/{vendor}/approve', [VendorController::class, 'approve'])
            ->name('vendors.approve');

        Route::patch('/vendors/{vendor}/suspend', [VendorController::class, 'suspend'])
            ->name('vendors.suspend');

        /*
        | Gestion des clients
        */
        Route::get('/clients', [ClientController::class, 'index'])
            ->name('clients.index');

        Route::get('/clients/{client}', [ClientController::class, 'show'])
            ->name('clients.show');

        Route::patch('/clients/{client}/block', [ClientController::class, 'block'])
            ->name('clients.block');

        Route::patch('/clients/{client}/activate', [ClientController::class, 'activate'])
            ->name('clients.activate');

        /*
        | Gestion des catégories
        */
        Route::get('/categories', [CategoryController::class, 'index'])
            ->name('categories.index');

        Route::get('/categories/create', [CategoryController::class, 'create'])
            ->name('categories.create');

        Route::post('/categories', [CategoryController::class, 'store'])
            ->name('categories.store');

        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])
            ->name('categories.edit');

        Route::put('/categories/{category}', [CategoryController::class, 'update'])
            ->name('categories.update');

        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
            ->name('categories.destroy');

        /*
        | Gestion des produits
        */
        Route::get('/products', [ProductController::class, 'index'])
            ->name('products.index');

        Route::get('/products/{product}', [ProductController::class, 'show'])
            ->name('products.show');

        Route::patch('/products/{product}/activate', [ProductController::class, 'activate'])
            ->name('products.activate');

        Route::patch('/products/{product}/deactivate', [ProductController::class, 'deactivate'])
            ->name('products.deactivate');

        /*
        | Gestion des commandes
        */
        Route::get('/orders', [OrderController::class, 'index'])
            ->name('orders.index');

        Route::get('/orders/{order}', [OrderController::class, 'show'])
            ->name('orders.show');

        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])
            ->name('orders.updateStatus');

        /*
        | Logs administrateur
        */
        Route::get('/logs', [AdminLogController::class, 'index'])
            ->name('logs.index');
    });

require __DIR__.'/client.php';

/*
|--------------------------------------------------------------------------
| Routes CHAT - Disponibles pour tous les utilisateurs authentifiés
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('chat')->name('chat.')->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('index');
    Route::get('/{conversation}', [ChatController::class, 'show'])->name('show');
    Route::post('/send/{conversation}', [ChatController::class, 'send'])->name('send');
    Route::get('/product/{product}', [ChatController::class, 'startFromProduct'])->name('product');
    Route::get('/order/{order}', [ChatController::class, 'startFromOrder'])->name('order');
    Route::get('/api/unread', [ChatController::class, 'getUnreadMessages'])->name('unread');
    Route::patch('/read/{message}', [ChatController::class, 'markAsRead'])->name('read');
    Route::delete('/delete/{conversation}', [ChatController::class, 'delete'])->name('delete');
    Route::get('/search', [ChatController::class, 'search'])->name('search');
});

/*
|--------------------------------------------------------------------------
| Route de TEST pour le chat
|--------------------------------------------------------------------------
*/
Route::get('/test-chat', function() {
    return view('test-chat');
})->name('test.chat')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Routes VENDEUR
|--------------------------------------------------------------------------
*/
require __DIR__ . '/vendeur.php';

/*
|--------------------------------------------------------------------------
| Authentification
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';

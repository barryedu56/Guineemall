<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\CatalogController;
use App\Http\Controllers\Client\CartController;
use App\Http\Controllers\Client\FavoriteController;
use App\Http\Controllers\Client\OrderController;
use App\Http\Controllers\Client\ProfileController;

/*
|--------------------------------------------------------------------------
| Routes CLIENT
|--------------------------------------------------------------------------
|
| Routes pour l'interface client de GuinéeMall
| Toutes les routes nécessitent une authentification et le rôle "client"
|
*/

Route::middleware(['auth', 'client'])
    ->prefix('client')
    ->name('client.')
    ->group(function () {

    /*
    |-------------------------------------------------
    | Tableau de bord client
    |-------------------------------------------------
    */
    Route::get('/dashboard', [ProfileController::class, 'dashboard'])
        ->name('dashboard');

    /*
    |-------------------------------------------------
    | Catalogue de produits
    |-------------------------------------------------
    */
    Route::prefix('catalog')->name('catalog.')->group(function () {
        Route::get('/', [CatalogController::class, 'index'])
            ->name('index');
            
        Route::get('/product/{product}', [CatalogController::class, 'show'])
            ->name('show');
            
        Route::get('/category/{category}', [CatalogController::class, 'category'])
            ->name('category');

        Route::get('/vendor/{vendor}', [CatalogController::class, 'vendor'])
            ->name('vendor');
    });

    /*
    |-------------------------------------------------
    | Gestion du panier
    |-------------------------------------------------
    */
    Route::prefix('cart')->name('cart.')->middleware('throttle:cart-actions')->group(function () {
        Route::get('/', [CartController::class, 'index'])
            ->name('index');
            
        Route::post('/add', [CartController::class, 'add'])
            ->name('add');
            
        Route::patch('/{cart}', [CartController::class, 'update'])
            ->name('update');
            
        Route::delete('/{cart}', [CartController::class, 'remove'])
            ->name('remove');
            
        Route::delete('/', [CartController::class, 'clear'])
            ->name('clear');
    });

    /*
    |-------------------------------------------------
    | Gestion des commandes
    |-------------------------------------------------
    */
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])
            ->name('index');
            
        Route::get('/checkout', [CartController::class, 'checkout'])
            ->name('checkout');
            
        Route::post('/', [OrderController::class, 'store'])
            ->middleware('throttle:order-create')
            ->name('store');
            
        Route::get('/{order}', [OrderController::class, 'show'])
            ->name('show');
            
        Route::patch('/{order}/cancel', [OrderController::class, 'cancel'])
            ->name('cancel');
    });

    /*
    |-------------------------------------------------
    | Favoris
    |-------------------------------------------------
    */
    Route::get('/favorites', [FavoriteController::class, 'index'])
        ->name('favorites.index');
    Route::post('/favorites/toggle/{product}', [FavoriteController::class, 'toggle'])
        ->name('favorites.toggle');

    /*
    |-------------------------------------------------
    | Gestion du profil client
    |-------------------------------------------------
    */
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])
            ->name('index');
            
        Route::get('/edit', [ProfileController::class, 'edit'])
            ->name('edit');
            
        Route::patch('/', [ProfileController::class, 'update'])
            ->name('update');
            
        Route::put('/password', [ProfileController::class, 'password'])
            ->name('password');
    });

    /*
    |-------------------------------------------------
    | Pages d'information (footer)
    |-------------------------------------------------
    */
    Route::view('/about', 'client.pages.about')->name('pages.about');
    Route::view('/how-it-works', 'client.pages.how')->name('pages.how');
    Route::view('/become-vendor', 'client.pages.become-vendor')->name('pages.become-vendor');
    Route::view('/contact', 'client.pages.contact')->name('pages.contact');
    Route::view('/faq', 'client.pages.faq')->name('pages.faq');
    Route::view('/delivery', 'client.pages.delivery')->name('pages.delivery');

});

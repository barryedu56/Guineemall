<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\VendorPolicy;
use App\Policies\VendorOrderPolicy;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
use App\Observers\VendorObserver;
use App\Observers\VendorSyncObserver;
use App\Observers\UserVendorObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Listeners\SendOrderCreatedNotifications;
use App\Listeners\SendOrderStatusChangedNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Vendor::class, VendorPolicy::class);
        Gate::policy(VendorOrder::class, VendorOrderPolicy::class);

        RateLimiter::for('order-create', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('cart-actions', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('product-create', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->id ?: $request->ip());
        });

        Event::listen(Login::class, function (Login $event) {
            $sessionId = session()->getId();
            $userId = $event->user->id;

            $guestItems = Cart::whereNull('user_id')
                ->where('session_id', $sessionId)
                ->with('product')
                ->get();

            if ($guestItems->isEmpty()) {
                return;
            }

            DB::transaction(function () use ($guestItems, $userId) {
                foreach ($guestItems as $guestItem) {
                    $product = $guestItem->product;
                    if (! $product) {
                        $guestItem->delete();
                        continue;
                    }

                    $target = Cart::where('user_id', $userId)
                        ->where('product_id', $guestItem->product_id)
                        ->first();

                    if ($target) {
                        $newQty = min($target->quantity + $guestItem->quantity, $product->stock);
                        $target->update(['quantity' => $newQty]);
                        $guestItem->delete();
                    } else {
                        $guestItem->update([
                            'user_id' => $userId,
                            'session_id' => null,
                        ]);
                    }
                }
            });
        });

        Event::listen(OrderCreated::class, SendOrderCreatedNotifications::class);
        Event::listen(OrderStatusChanged::class, SendOrderStatusChangedNotification::class);

        Order::observe(OrderObserver::class);
        Product::observe(ProductObserver::class);
        Vendor::observe(VendorObserver::class);
        Vendor::observe(VendorSyncObserver::class);
        User::observe(UserVendorObserver::class);
    }
}

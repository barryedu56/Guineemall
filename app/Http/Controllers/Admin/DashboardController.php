<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Category;
use App\Models\Vendor;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Dashboard admin professionnel avec statistiques avancées
     */
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Statistiques principales
        |--------------------------------------------------------------------------
        */
        $clientsCount = User::where('role', 'client')->count();
        $vendeursCount = User::where('role', 'vendeur')->count();
        $productsCount = Product::count();
        $ordersCount = Order::count();
        $categoriesCount = Category::count();

        /*
        |--------------------------------------------------------------------------
        | Commandes par statut
        |--------------------------------------------------------------------------
        */
        $ordersPreparing = Order::where('status', 'processing')->count();
        $ordersDelivered = Order::where('status', 'delivered')->count();
        $ordersCancelled = Order::where('status', 'cancelled')->count();
        $ordersPending = Order::where('status', 'pending')->count();

        /*
        |--------------------------------------------------------------------------
        | Chiffre d'affaires et revenus
        |--------------------------------------------------------------------------
        */
        $chiffreAffaires = Order::where('status', 'delivered')->sum('total_amount');
        $monthlyRevenue = Order::where('status', 'delivered')
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('total_amount');
        $todayRevenue = Order::where('status', 'delivered')
            ->whereDate('created_at', Carbon::today())
            ->sum('total_amount');

        /*
        |--------------------------------------------------------------------------
        | Statistiques de croissance (comparaison mois précédent)
        |--------------------------------------------------------------------------
        */
        $lastMonthClients = User::where('role', 'client')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->count();
        $thisMonthClients = User::where('role', 'client')
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();
        $clientsGrowth = $lastMonthClients > 0 ? 
            (($thisMonthClients - $lastMonthClients) / $lastMonthClients) * 100 : 0;

        $lastMonthOrders = Order::whereMonth('created_at', Carbon::now()->subMonth()->month)->count();
        $thisMonthOrders = Order::whereMonth('created_at', Carbon::now()->month)->count();
        $ordersGrowth = $lastMonthOrders > 0 ? 
            (($thisMonthOrders - $lastMonthOrders) / $lastMonthOrders) * 100 : 0;

        /*
        |--------------------------------------------------------------------------
        | Produits et catégories populaires
        |--------------------------------------------------------------------------
        */
        $topProducts = Product::with(['category', 'vendor'])
            ->withCount(['orderItems' => function($query) {
                $query->whereHas('order', function($q) {
                    $q->where('status', 'delivered');
                });
            }])
            ->orderBy('order_items_count', 'desc')
            ->take(5)
            ->get();

        $topCategories = Category::withCount(['products' => function($query) {
                $query->where('status', 'active');
            }])
            ->orderBy('products_count', 'desc')
            ->take(6)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Activité récente
        |--------------------------------------------------------------------------
        */
        $recentOrders = Order::with(['user'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $recentUsers = User::orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $recentProducts = Product::with(['category', 'vendor'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Vendeurs en attente d'approbation
        |--------------------------------------------------------------------------
        */
        $pendingVendors = Vendor::where('status', 'pending')->count();

        /*
        |--------------------------------------------------------------------------
        | Produits en rupture de stock
        |--------------------------------------------------------------------------
        */
        $lowStockProducts = Product::where('stock', '<=', 5)
            ->where('stock', '>', 0)
            ->count();

        $outOfStockProducts = Product::where('stock', '<=', 0)->count();

        /*
        |--------------------------------------------------------------------------
        | Données pour graphiques (derniers 7 jours)
        |--------------------------------------------------------------------------
        */
        $dailyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyStats[] = [
                'date' => $date->format('d/m'),
                'orders' => Order::whereDate('created_at', $date->format('Y-m-d'))->count(),
                'revenue' => Order::where('status', 'delivered')
                    ->whereDate('created_at', $date->format('Y-m-d'))
                    ->sum('total_amount'),
                'users' => User::whereDate('created_at', $date->format('Y-m-d'))->count(),
            ];
        }

        return view('admin.dashboard', compact(
            'clientsCount', 'vendeursCount', 'productsCount', 'ordersCount', 'categoriesCount',
            'ordersPreparing', 'ordersDelivered', 'ordersCancelled', 'ordersPending',
            'chiffreAffaires', 'monthlyRevenue', 'todayRevenue',
            'clientsGrowth', 'ordersGrowth',
            'topProducts', 'topCategories',
            'recentOrders', 'recentUsers', 'recentProducts',
            'pendingVendors', 'lowStockProducts', 'outOfStockProducts',
            'dailyStats'
        ));
    }
}

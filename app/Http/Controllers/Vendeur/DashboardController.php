<?php

namespace App\Http\Controllers\Vendeur;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\VendorOrder;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Afficher le tableau de bord du vendeur
     */
    public function index(): View
    {
        $vendor = Auth::user()->vendor;
        
        // Statistiques produits
        $totalProducts = Product::where('vendor_id', $vendor->id)->count();
        $outOfStockProducts = Product::where('vendor_id', $vendor->id)
            ->where('stock', '<=', 0)
            ->count();
        $activeProducts = Product::where('vendor_id', $vendor->id)
            ->where('status', 'active')
            ->count();
        
        // Statistiques commandes
        $totalOrders = VendorOrder::where('vendor_id', $vendor->id)->count();
        $pendingOrders = VendorOrder::where('vendor_id', $vendor->id)
            ->where('status', 'pending')
            ->count();
        $deliveredOrders = VendorOrder::where('vendor_id', $vendor->id)
            ->where('status', 'delivered')
            ->count();
        
        // Chiffre d'affaires
        $totalRevenue = VendorOrder::where('vendor_id', $vendor->id)
            ->where('status', 'delivered')
            ->sum('total_amount');
        
        // Dernières commandes
        $recentOrders = VendorOrder::where('vendor_id', $vendor->id)
            ->with('order.user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Produits à réapprovisionner
        $lowStockProducts = Product::where('vendor_id', $vendor->id)
            ->where('stock', '<=', 5)
            ->where('stock', '>', 0)
            ->limit(5)
            ->get();
        
        return view('vendeur.dashboard', compact(
            'vendor',
            'totalProducts',
            'outOfStockProducts',
            'activeProducts',
            'totalOrders',
            'pendingOrders',
            'deliveredOrders',
            'totalRevenue',
            'recentOrders',
            'lowStockProducts'
        ));
    }
    
    /**
     * API pour les statistiques du dashboard (chart)
     */
    public function stats(Request $request)
    {
        $vendor = Auth::user()->vendor;
        $period = $request->get('period', 'month'); // week, month, year
        
        $query = VendorOrder::where('vendor_id', $vendor->id)
            ->where('status', 'delivered');
            
        switch ($period) {
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                $groupBy = 'DATE(created_at)';
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                $groupBy = 'MONTH(created_at)';
                break;
            default: // month
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                $groupBy = 'DATE(created_at)';
                break;
        }
        
        $stats = $query->selectRaw($groupBy . ' as period, SUM(total_amount) as revenue, COUNT(*) as orders')
            ->groupBy($groupBy)
            ->orderBy('period')
            ->get();
            
        return response()->json($stats);
    }
}

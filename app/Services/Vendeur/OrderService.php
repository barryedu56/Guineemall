<?php

namespace App\Services\Vendeur;

use App\Models\VendorOrder;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderService
{
    /**
     * Obtenir les commandes d'un vendeur avec filtres
     */
    public function getVendorOrders(int $vendorId, array $filters = [])
    {
        $query = VendorOrder::where('vendor_id', $vendorId)
            ->with(['order.user', 'orderItems.product']);
            
        // Filtre par statut
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        // Filtre par période
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        
        // Recherche
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('order', function ($orderQuery) use ($search) {
                    $orderQuery->where('id', 'like', "%{$search}%")
                             ->orWhereHas('user', function ($userQuery) use ($search) {
                                 $userQuery->where('name', 'like', "%{$search}%")
                                           ->orWhere('email', 'like', "%{$search}%");
                             });
                });
            });
        }
        
        return $query->orderBy('created_at', 'desc')->paginate(15);
    }
    
    /**
     * Obtenir les détails d'une commande vendeur
     */
    public function getVendorOrder(int $vendorId, int $orderId): VendorOrder
    {
        $order = VendorOrder::where('vendor_id', $vendorId)
            ->where('id', $orderId)
            ->with(['order.user', 'orderItems.product'])
            ->firstOrFail();
            
        return $order;
    }
    
    /**
     * Mettre à jour le statut d'une commande
     */
    public function updateOrderStatus(VendorOrder $order, string $status): VendorOrder
    {
        $this->verifyOrderOwnership($order);
        
        // Vérifier que le statut est valide
        $validStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException('Statut invalide.');
        }
        
        // Interdire de modifier une commande livrée
        if ($order->status === 'delivered' && $status !== 'delivered') {
            throw new \Exception('Impossible de modifier une commande déjà livrée.');
        }
        
        // Historique des changements de statut
        $oldStatus = $order->status;
        
        DB::beginTransaction();
        try {
            $order->update(['status' => $status]);
            $this->updateMainOrderStatusFromVendorOrders($order->order);
            
            // Log du changement de statut (pour traçabilité)
            $this->logStatusChange($order, $oldStatus, $status);
            
            DB::commit();

            return $order->fresh();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function updateMainOrderStatusFromVendorOrders(\App\Models\Order $order): void
    {
        $vendorOrders = $order->vendorOrders;

        if ($vendorOrders->isEmpty()) {
            return;
        }

        $allCancelled = $vendorOrders->every(fn($vo) => $vo->status === 'cancelled');
        $allDelivered = $vendorOrders->every(fn($vo) => $vo->status === 'delivered');
        $anyProcessing = $vendorOrders->contains(fn($vo) => in_array($vo->status, ['confirmed', 'preparing', 'ready']));

        if ($allCancelled) {
            $order->update(['status' => 'cancelled']);
        } elseif ($allDelivered) {
            $order->update(['status' => 'delivered']);
        } elseif ($anyProcessing) {
            $order->update(['status' => 'processing']);
        }
    }
    
    /**
     * Calculer les statistiques des commandes
     */
    public function getOrderStats(int $vendorId, string $period = 'month'): array
    {
        $query = VendorOrder::where('vendor_id', $vendorId);
        
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }
        
        $baseQuery = clone $query;

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'confirmed' => (clone $baseQuery)->where('status', 'confirmed')->count(),
            'preparing' => (clone $baseQuery)->where('status', 'preparing')->count(),
            'ready' => (clone $baseQuery)->where('status', 'ready')->count(),
            'delivered' => (clone $baseQuery)->where('status', 'delivered')->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
            'revenue' => (clone $baseQuery)->where('status', 'delivered')->sum('total_amount'),
        ];
        
        return $stats;
    }
    
    /**
     * Obtenir les commandes récentes
     */
    public function getRecentOrders(int $vendorId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return VendorOrder::where('vendor_id', $vendorId)
            ->with(['order.user', 'orderItems.product'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Générer un rapport des ventes
     */
    public function generateSalesReport(int $vendorId, array $filters = []): array
    {
        $query = VendorOrder::where('vendor_id', $vendorId)
            ->where('status', 'delivered')
            ->with(['orderItems.product']);
            
        // Filtre par période
        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }
        
        $orders = $query->get();
        
        $report = [
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('total_amount'),
            'average_order_value' => $orders->count() > 0 ? $orders->sum('total_amount') / $orders->count() : 0,
            'products_sold' => 0,
            'top_products' => [],
        ];
        
        // Calculer les produits les plus vendus
        $productSales = [];
        foreach ($orders as $order) {
            foreach ($order->orderItems as $item) {
                $productName = $item->product->name;
                if (!isset($productSales[$productName])) {
                    $productSales[$productName] = [
                        'name' => $productName,
                        'quantity' => 0,
                        'revenue' => 0,
                    ];
                }
                $productSales[$productName]['quantity'] += $item->quantity;
                $productSales[$productName]['revenue'] += $item->quantity * $item->price;
                $report['products_sold'] += $item->quantity;
            }
        }
        
        // Trier par quantité décroissante
        uasort($productSales, function ($a, $b) {
            return $b['quantity'] - $a['quantity'];
        });
        
        $report['top_products'] = array_slice($productSales, 0, 10);
        
        return $report;
    }
    
    /**
     * Vérifier que la commande appartient au vendeur
     */
    private function verifyOrderOwnership(VendorOrder $order): void
    {
        if ($order->vendor_id !== auth()->user()->vendor->id) {
            abort(403, 'Cette commande ne vous appartient pas.');
        }
    }
    
    /**
     * Logger les changements de statut
     */
    private function logStatusChange(VendorOrder $order, string $oldStatus, string $newStatus): void
    {
        // Pourrait être implémenté avec une table de logs si nécessaire
        // Pour l'instant, on utilise les logs Laravel
        \Log::info('Changement de statut commande', [
            'order_id' => $order->id,
            'vendor_id' => $order->vendor_id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'user_id' => auth()->id(),
            'timestamp' => now(),
        ]);
    }
}

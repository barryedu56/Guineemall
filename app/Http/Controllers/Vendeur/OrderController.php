<?php

namespace App\Http\Controllers\Vendeur;

use App\Http\Controllers\Controller;
use App\Services\Vendeur\OrderService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use App\Models\VendorOrder;

class OrderController extends Controller
{
    protected OrderService $orderService;
    
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    
    /**
     * Afficher la liste des commandes du vendeur
     */
    public function index(Request $request): View
    {
        $vendorId = auth()->user()->vendor->id;
        
        $filters = [
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
        ];

        if ($request->filled('period') && ! $request->filled('date_from') && ! $request->filled('date_to')) {
            switch ($request->get('period')) {
                case 'today':
                    $filters['date_from'] = now()->toDateString();
                    $filters['date_to'] = now()->toDateString();
                    break;
                case 'week':
                    $filters['date_from'] = now()->startOfWeek()->toDateString();
                    $filters['date_to'] = now()->endOfWeek()->toDateString();
                    break;
                case 'month':
                    $filters['date_from'] = now()->startOfMonth()->toDateString();
                    $filters['date_to'] = now()->endOfMonth()->toDateString();
                    break;
                case 'year':
                    $filters['date_from'] = now()->startOfYear()->toDateString();
                    $filters['date_to'] = now()->endOfYear()->toDateString();
                    break;
            }
        }
        
        $orders = $this->orderService->getVendorOrders($vendorId, $filters);
        
        // Statistiques rapides
        $stats = $this->orderService->getOrderStats($vendorId, 'month');
        
        return view('vendeur.orders.index', compact('orders', 'filters', 'stats'));
    }
    
    /**
     * Afficher les détails d'une commande
     */
    public function show(int $id): View
    {
        $vendorId = auth()->user()->vendor->id;
        $order = $this->orderService->getVendorOrder($vendorId, $id);

        $this->authorize('view', $order);
        
        return view('vendeur.orders.show', compact('order'));
    }
    
    /**
     * Mettre à jour le statut d'une commande
     */
    public function updateStatus(Request $request, VendorOrder $order): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $order);
        try {
            $request->validate([
                'status' => 'required|in:pending,confirmed,preparing,ready,delivered,cancelled'
            ]);
            
            $this->orderService->updateOrderStatus($order, $request->status);
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('messages.vendor_orders.status_updated'),
                ]);
            }

            return redirect()
                ->back()
                ->with('success', __('messages.vendor_orders.status_updated'));
                
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour du statut: ' . $e->getMessage(),
                ], 400);
            }

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la mise à jour du statut: ' . $e->getMessage());
        }
    }
    
    /**
     * Confirmer une commande
     */
    public function confirm(VendorOrder $order): RedirectResponse
    {
        $this->authorize('update', $order);
        try {
            $this->orderService->updateOrderStatus($order, 'confirmed');
            
            return redirect()
                ->back()
                ->with('success', __('messages.vendor_orders.confirmed'));
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la confirmation: ' . $e->getMessage());
        }
    }
    
    /**
     * Marquer une commande comme en préparation
     */
    public function preparing(VendorOrder $order): RedirectResponse
    {
        $this->authorize('update', $order);
        try {
            $this->orderService->updateOrderStatus($order, 'preparing');
            
            return redirect()
                ->back()
                ->with('success', __('messages.vendor_orders.preparing'));
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
    
    /**
     * Marquer une commande comme prête
     */
    public function ready(VendorOrder $order): RedirectResponse
    {
        $this->authorize('update', $order);
        try {
            $this->orderService->updateOrderStatus($order, 'ready');
            
            return redirect()
                ->back()
                ->with('success', __('messages.vendor_orders.ready'));
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
    
    /**
     * Marquer une commande comme livrée
     */
    public function delivered(VendorOrder $order): RedirectResponse
    {
        $this->authorize('update', $order);
        try {
            $this->orderService->updateOrderStatus($order, 'delivered');
            
            return redirect()
                ->back()
                ->with('success', __('messages.vendor_orders.delivered'));
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
    
    /**
     * Annuler une commande
     */
    public function cancel(Request $request, VendorOrder $order): RedirectResponse
    {
        $this->authorize('update', $order);
        try {
            $request->validate([
                'cancel_reason' => 'required|string|max:500'
            ]);
            
            $this->orderService->updateOrderStatus($order, 'cancelled');
            
            // Pourrait enregistrer la raison d'annulation dans une table séparée
            
            return redirect()
                ->back()
                ->with('success', __('messages.vendor_orders.cancelled'));
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de l\'annulation: ' . $e->getMessage());
        }
    }
    
    /**
     * Exporter les commandes (CSV/Excel)
     */
    public function export(Request $request)
    {
        $vendorId = auth()->user()->vendor->id;
        
        $filters = [
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];
        
        $orders = $this->orderService->getVendorOrders($vendorId, $filters);
        
        // Format pour l'export
        $exportData = [];
        foreach ($orders as $order) {
            $exportData[] = [
                'ID Commande' => $order->order->id,
                'Client' => $order->order->user->name,
                'Email Client' => $order->order->user->email,
                'Montant' => $order->total_amount,
                'Statut' => $order->status,
                'Date' => $order->created_at->format('d/m/Y H:i'),
            ];
        }
        
        // Génération du CSV
        $filename = 'commandes_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function () use ($exportData) {
            $file = fopen('php://output', 'w');
            
            // En-têtes
            fputcsv($file, array_keys($exportData[0] ?? []));
            
            // Données
            foreach ($exportData as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * API pour obtenir les statistiques des commandes
     */
    public function stats(Request $request)
    {
        $vendorId = auth()->user()->vendor->id;
        $period = $request->get('period', 'month');
        
        $stats = $this->orderService->getOrderStats($vendorId, $period);
        
        return response()->json($stats);
    }
    
    /**
     * Rapport des ventes
     */
    public function salesReport(Request $request): View
    {
        $vendorId = auth()->user()->vendor->id;
        
        $filters = [
            'start_date' => $request->get('start_date', now()->startOfMonth()->format('Y-m-d')),
            'end_date' => $request->get('end_date', now()->format('Y-m-d')),
        ];
        
        $report = $this->orderService->generateSalesReport($vendorId, $filters);
        
        return view('vendeur.orders.report', compact('report', 'filters'));
    }
}

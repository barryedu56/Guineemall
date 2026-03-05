<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Liste des commandes (admin)
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'vendorOrders.vendor']);

        // Filtrer par statut si fourni
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Détail d'une commande
     */
    public function show(Order $order)
    {
        $order->load([
            'user',
            'vendorOrders.vendor',
            'items.product'
        ]);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Mettre à jour le statut d’une commande
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,delivered,cancelled',
        ]);

        $newStatus = $request->status;

        DB::transaction(function () use ($order, $newStatus) {
            $order->update([
                'status' => $newStatus,
            ]);

            if ($newStatus === 'delivered') {
                $order->vendorOrders()
                    ->where('status', '!=', 'cancelled')
                    ->update(['status' => 'delivered']);
            }

            if ($newStatus === 'cancelled') {
                $order->vendorOrders()
                    ->where('status', '!=', 'delivered')
                    ->update(['status' => 'cancelled']);
            }
        });

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Statut de la commande mis à jour avec succès.');
    }
}

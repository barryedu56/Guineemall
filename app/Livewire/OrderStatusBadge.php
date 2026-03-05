<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Component;

class OrderStatusBadge extends Component
{
    public $orderId;
    public $order;

    public function mount($orderId)
    {
        $this->orderId = $orderId;
        $this->order = Order::find($orderId);
    }

    public function render()
    {
        return view('livewire.order-status-badge');
    }

    /**
     * Obtenir la classe CSS pour le statut
     */
    public function getStatusClass()
    {
        if (!$this->order) return 'bg-gray-100 text-gray-800';

        return match($this->order->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'processing' => 'bg-blue-100 text-blue-800',
            'shipped' => 'bg-purple-100 text-purple-800',
            'delivered' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'refunded' => 'bg-orange-100 text-orange-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Obtenir le texte du statut en français
     */
    public function getStatusText()
    {
        if (!$this->order) return 'Inconnu';

        return match($this->order->status) {
            'pending' => 'En attente',
            'processing' => 'En préparation',
            'shipped' => 'Expédiée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
            'refunded' => 'Remboursée',
            default => ucfirst($this->order->status)
        };
    }

    /**
     * Obtenir l'icône du statut
     */
    public function getStatusIcon()
    {
        if (!$this->order) return 'fas fa-question';

        return match($this->order->status) {
            'pending' => 'fas fa-clock',
            'processing' => 'fas fa-cog',
            'shipped' => 'fas fa-truck',
            'delivered' => 'fas fa-check-circle',
            'cancelled' => 'fas fa-times-circle',
            'refunded' => 'fas fa-undo',
            default => 'fas fa-info-circle'
        };
    }
}

<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\VendorOrder;

class VendorOrderStatusBadge extends Component
{
    public $status;
    public $vendorOrderId;

    public function mount($status = null, $vendorOrderId = null)
    {
        $this->status = $status;
        $this->vendorOrderId = $vendorOrderId;
    }

    public function getStatusColor()
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            'confirmed' => 'bg-blue-100 text-blue-800 border-blue-200',
            'preparing' => 'bg-purple-100 text-purple-800 border-purple-200',
            'ready' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            'delivered' => 'bg-green-100 text-green-800 border-green-200',
            'cancelled' => 'bg-red-100 text-red-800 border-red-200',
            default => 'bg-gray-100 text-gray-800 border-gray-200',
        };
    }

    public function getStatusIcon()
    {
        return match($this->status) {
            'pending' => 'fas fa-clock',
            'confirmed' => 'fas fa-check-circle',
            'preparing' => 'fas fa-box',
            'ready' => 'fas fa-truck',
            'delivered' => 'fas fa-check-double',
            'cancelled' => 'fas fa-times-circle',
            default => 'fas fa-question-circle',
        };
    }

    public function getStatusText()
    {
        return match($this->status) {
            'pending' => 'En attente',
            'confirmed' => 'Confirmée',
            'preparing' => 'En préparation',
            'ready' => 'Prête',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
            default => 'Inconnue',
        };
    }

    public function render()
    {
        return view('livewire.vendor-order-status-badge');
    }
}

@props(['order'])

@if($order)
<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ getStatusClass($order) }}">
    <i class="{{ getStatusIcon($order) }} mr-1"></i>
    {{ getStatusText($order) }}
</span>
@else
    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
        <i class="fas fa-question mr-1"></i>
        Inconnu
    </span>
@endif

@php
    function getStatusClass($order)
    {
        return match($order->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'processing' => 'bg-blue-100 text-blue-800',
            'shipped' => 'bg-orange-100 text-orange-800',
            'delivered' => 'bg-yellow-100 text-yellow-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'refunded' => 'bg-orange-100 text-orange-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    function getStatusText($order)
    {
        return match($order->status) {
            'pending' => 'En attente',
            'processing' => 'En préparation',
            'shipped' => 'Expédiée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
            'refunded' => 'Remboursée',
            default => ucfirst($order->status)
        };
    }

    function getStatusIcon($order)
    {
        return match($order->status) {
            'pending' => 'fas fa-clock',
            'processing' => 'fas fa-cog',
            'shipped' => 'fas fa-truck',
            'delivered' => 'fas fa-check-circle',
            'cancelled' => 'fas fa-times-circle',
            'refunded' => 'fas fa-undo',
            default => 'fas fa-info-circle'
        };
    }
@endphp
<!-- Life is available only in the present moment. - Thich Nhat Hanh -->
<?php

namespace App\Observers;

use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\OrderStatusHistory;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $actorId = auth()->id();

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'changed_by' => $actorId,
        ]);

        event(new OrderStatusChanged($order, $order->status, $actorId));
    }
}

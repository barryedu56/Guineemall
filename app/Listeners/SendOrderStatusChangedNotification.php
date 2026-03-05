<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Jobs\SendClientNotificationJob;
use App\Notifications\OrderStatusChangedForClient;

class SendOrderStatusChangedNotification
{
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order->loadMissing('user');

        if ($order->user) {
            SendClientNotificationJob::dispatch(
                $order->user,
                new OrderStatusChangedForClient($order, $event->status)
            );
        }
    }
}

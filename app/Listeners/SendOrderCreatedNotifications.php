<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Jobs\SendVendorNotificationJob;
use App\Notifications\NewOrderForVendor;

class SendOrderCreatedNotifications
{
    public function handle(OrderCreated $event): void
    {
        $order = $event->order->loadMissing('vendorOrders.vendor.user');

        foreach ($order->vendorOrders as $vendorOrder) {
            $user = $vendorOrder->vendor?->user;
            if ($user) {
                SendVendorNotificationJob::dispatch(
                    $user,
                    new NewOrderForVendor($vendorOrder)
                );
            }
        }
    }
}

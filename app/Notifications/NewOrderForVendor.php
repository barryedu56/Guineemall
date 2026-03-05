<?php

namespace App\Notifications;

use App\Models\VendorOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewOrderForVendor extends Notification
{
    use Queueable;

    public function __construct(private readonly VendorOrder $vendorOrder)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('messages.notifications.new_order_title'),
            'message' => __('messages.notifications.new_order_message', [
                'id' => $this->vendorOrder->id,
            ]),
            'action_url' => route('vendeur.orders.show', $this->vendorOrder),
            'action_text' => __('messages.notifications.new_order_action'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('messages.notifications.new_order_title'))
            ->line(__('messages.notifications.new_order_message', [
                'id' => $this->vendorOrder->id,
            ]))
            ->action(__('messages.notifications.new_order_action'), route('vendeur.orders.show', $this->vendorOrder));
    }
}

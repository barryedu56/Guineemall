<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ProductRejectedForVendor extends Notification
{
    use Queueable;

    public function __construct(private readonly Product $product)
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
            'title' => __('messages.notifications.product_rejected_title'),
            'message' => __('messages.notifications.product_rejected_message', [
                'product' => $this->product->name,
            ]),
            'action_url' => route('vendeur.products.show', $this->product),
            'action_text' => __('messages.notifications.product_rejected_action'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('messages.notifications.product_rejected_title'))
            ->line(__('messages.notifications.product_rejected_message', [
                'product' => $this->product->name,
            ]))
            ->action(__('messages.notifications.product_rejected_action'), route('vendeur.products.show', $this->product));
    }
}

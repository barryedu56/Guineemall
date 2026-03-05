<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrderStatusChangedForClient extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly string $newStatus
    ) {
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
        $statusLabel = $this->formatStatus($this->newStatus);

        return [
            'title' => __('messages.notifications.order_status_title'),
            'message' => __('messages.notifications.order_status_message', [
                'id' => $this->order->id,
                'status' => $statusLabel,
            ]),
            'action_url' => route('client.orders.show', $this->order),
            'action_text' => __('messages.notifications.order_status_action'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = $this->formatStatus($this->newStatus);

        return (new MailMessage)
            ->subject(__('messages.notifications.order_status_title'))
            ->line(__('messages.notifications.order_status_message', [
                'id' => $this->order->id,
                'status' => $statusLabel,
            ]))
            ->action(__('messages.notifications.order_status_action'), route('client.orders.show', $this->order));
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'En attente',
            'processing' => 'En traitement',
            'confirmed' => 'Confirmée',
            'preparing' => 'En préparation',
            'ready' => 'Prête',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
            default => $status,
        };
    }
}

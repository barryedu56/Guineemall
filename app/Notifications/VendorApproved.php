<?php

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class VendorApproved extends Notification
{
    use Queueable;

    public function __construct(private readonly Vendor $vendor)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $shopName = $this->vendor->shop_name ?? 'votre boutique';

        return [
            'title' => __('messages.notifications.vendor_approved_title'),
            'message' => __('messages.notifications.vendor_approved_message', ['shop' => $shopName]),
            'action_url' => route('vendeur.dashboard'),
            'action_text' => __('messages.notifications.vendor_approved_action'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $shopName = $this->vendor->shop_name ?? 'votre boutique';

        return (new MailMessage)
            ->subject(__('messages.notifications.vendor_approved_title'))
            ->line(__('messages.notifications.vendor_approved_message', ['shop' => $shopName]))
            ->action(__('messages.notifications.vendor_approved_action'), route('vendeur.dashboard'));
    }
}

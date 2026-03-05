<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private int $conversationId,
        private string $senderName,
        private string $message,
        private ?int $senderId = null
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'sender' => $this->senderName,
            'message' => $this->message,
            'sender_id' => $this->senderId,
            'type' => 'new_message',
            'created_at' => now()->toISOString(),
        ];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'sender' => $this->senderName,
            'message' => $this->message,
            'sender_id' => $this->senderId,
            'type' => 'new_message',
        ];
    }
}

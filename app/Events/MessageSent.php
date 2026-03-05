<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Le message qui a été envoyé
     *
     * @var \App\Models\Message
     */
    public $message;

    /**
     * Créer un nouvel événement
     *
     * @param \App\Models\Message $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Définir le canal de diffusion
     * Canal privé pour la conversation spécifique
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Canal privé pour la conversation
        return new PrivateChannel('conversation.' . $this->getConversationId());
    }

    /**
     * Obtenir l'ID de la conversation
     *
     * @return string
     */
    private function getConversationId()
    {
        // Utiliser l'ID du message comme ID de conversation pour simplifier
        // Dans une implémentation plus avancée, vous pourriez avoir
        // une table conversations séparée
        return $this->message->id;
    }

    /**
     * Données à diffuser avec l'événement
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'message' => $this->message,
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
                'role' => $this->message->sender->role,
            ],
            'receiver' => [
                'id' => $this->message->receiver->id,
                'name' => $this->message->receiver->name,
                'role' => $this->message->receiver->role,
            ],
            'timestamp' => $this->message->created_at->toISOString(),
        ];
    }

    /**
     * Nom de l'événement pour le frontend
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'message.sent';
    }
}

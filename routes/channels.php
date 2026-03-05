<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user is able to listen to the channel.
|
*/

Broadcast::channel('conversation.{id}', function ($user, $id) {
    // Vérifier si l'utilisateur est autorisé à écouter ce canal de conversation
    $message = \App\Models\Message::where('conversation_id', $id)
        ->where(function ($query) use ($user) {
            $query->where('sender_id', $user->id)
                  ->orWhere('receiver_id', $user->id);
        })
        ->first();
    
    // Autoriser uniquement si l'utilisateur fait partie de la conversation
    return $message !== null;
});

// Canal privé pour les notifications de nouveaux messages
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Canal pour les vendeurs - notifications de nouvelles commandes/messages
Broadcast::channel('vendor.{vendorId}', function ($user, $vendorId) {
    // Autoriser uniquement si l'utilisateur est le vendeur concerné
    return $user->role === 'vendeur' && $user->id === (int) $vendorId;
});

// Canal pour les administrateurs - notifications système
Broadcast::channel('admin', function ($user) {
    // Autoriser uniquement les administrateurs
    return $user->role === 'admin';
});

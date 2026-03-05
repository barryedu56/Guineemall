<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NewMessageNotification;
use Illuminate\Support\Facades\Notification;

class MessageController extends Controller
{
    /**
     * Liste des conversations/messages pour l'admin
     */
    public function index(Request $request): View
    {
        $query = User::with(['vendor', 'messages'])
            ->whereHas('messages')
            ->where('role', '!=', 'admin');

        // Recherche
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        $conversations = $query->latest('last_message_at')->paginate(20);

        // Calculer les messages non lus
        $unreadCount = Message::whereNull('read_at')
            ->where('receiver_id', auth()->id())
            ->count();

        return view('admin.messages.index', compact('conversations', 'unreadCount'));
    }

    /**
     * Afficher une conversation avec un utilisateur
     */
    public function show(User $user, Request $request): View
    {
        // Vérifier que l'utilisateur n'est pas un admin
        if ($user->role === 'admin') {
            abort(403, 'Vous ne pouvez pas discuter avec un autre administrateur');
        }

        // Marquer les messages comme lus
        Message::where('sender_id', $user->id)
            ->where('receiver_id', auth()->id())
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        // Récupérer les messages
        $messages = Message::where(function($query) use ($user) {
                $query->where('sender_id', auth()->id())
                      ->where('receiver_id', $user->id)
                      ->orWhere('sender_id', $user->id)
                      ->where('receiver_id', auth()->id());
            })
            ->with(['sender', 'receiver', 'product', 'order'])
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return view('admin.messages.show', compact('user', 'messages'));
    }

    /**
     * Envoyer un message
     */
    public function send(Request $request, User $receiver): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'product_id' => 'nullable|exists:products,id',
            'order_id' => 'nullable|exists:orders,id',
            'type' => 'in:text,image,file',
        ]);

        $sender = Auth::user();

        // Vérifier que l'admin peut discuter avec cette personne
        if ($receiver->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas discuter avec un autre administrateur'
            ], 403);
        }

        // 1) Vérifier si la conversation existe
        $conversation = Conversation::where(function($query) use ($sender, $receiver) {
            $query->where('user_id', $sender->id)
                  ->where('vendor_id', $receiver->id)
                  ->orWhere('user_id', $receiver->id)
                  ->where('vendor_id', $sender->id);
        })->first();

        // 2) Créer la conversation si elle n'existe pas
        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => $sender->role === 'client' ? $sender->id : $receiver->id,
                'vendor_id' => $sender->role === 'vendeur' ? $sender->id : $receiver->id,
            ]);
        }

        // 3) Créer le message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'content' => $request->content,
            'product_id' => $request->product_id,
            'order_id' => $request->order_id,
            'type' => $request->type ?? 'text',
        ]);

        // 4) Charger les relations pour la réponse
        $message->load(['sender', 'receiver', 'product', 'order', 'conversation']);
        
        // Envoyer la notification au destinataire
        Notification::send($receiver, new NewMessageNotification(
            conversationId: $message->id,
            senderName: $sender->name,
            message: $message->content,
            senderId: $sender->id
        ));
        
        // Émettre l'événement de diffusion pour le temps réel
        broadcast(new \App\Events\MessageSent($message))->toOthers();
        
        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Conversation;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Afficher la liste des conversations
     */
    public function index(): View
    {
        $user = Auth::user();
        
        // Récupérer les conversations selon le rôle
        if ($user->role === 'client') {
            // Client voit ses conversations avec les vendeurs
            $conversations = Conversation::where('user_id', $user->id)
                ->with(['vendor', 'messages' => function($query) {
                    $query->latest()->limit(1);
                }])
                ->get();
        } elseif ($user->role === 'vendeur') {
            // Vendeur voit ses conversations avec les clients
            $conversations = Conversation::where('vendor_id', $user->id)
                ->with(['user', 'messages' => function($query) {
                    $query->latest()->limit(1);
                }])
                ->get();
        } else {
            // Admin voit toutes les conversations
            $conversations = Conversation::with(['user', 'vendor', 'messages' => function($query) {
                $query->latest()->limit(1);
            }])->get();
        }

        // Transformer les conversations pour l'affichage
        $conversations = $conversations->map(function ($conversation) use ($user) {
            $otherUser = $conversation->getOtherParticipant($user->id);
            $lastMessage = $conversation->messages->first();
            
            return (object) [
                'id' => $conversation->id,  // Changé: conversation_id au lieu de user_id
                'name' => $otherUser->name,
                'role' => $otherUser->role,
                'avatar' => $otherUser->avatar ?? null,
                'shop_name' => $otherUser->vendor->shop_name ?? null,
                'last_message' => $lastMessage ? $lastMessage->content : null,
                'last_message_time' => $lastMessage ? $lastMessage->created_at->format('H:i') : null,
                'unread_count' => Message::where('conversation_id', $conversation->id)
                    ->where('receiver_id', $user->id)
                    ->where('is_read', false)
                    ->count(),
                'conversation_id' => $conversation->id,
            ];
        });

        // Compter les messages non lus
        $unreadCount = Message::where('receiver_id', $user->id)
            ->where('is_read', false)
            ->count();

        return view('chat.index', compact('conversations', 'unreadCount'));
    }

    /**
     * Afficher une conversation spécifique
     */
    public function show(Conversation $conversation): View
    {
        $currentUser = Auth::user();
        
        // Vérifier si l'utilisateur peut accéder à cette conversation
        $otherUser = $conversation->getOtherParticipant($currentUser->id);
        
        if (!$this->canChatWith($currentUser, $otherUser)) {
            abort(403, 'Vous ne pouvez pas accéder à cette conversation');
        }

        // Marquer les messages comme lus
        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', $otherUser->id)
            ->where('receiver_id', $currentUser->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        // Récupérer les messages de la conversation
        $conversation->load('messages.sender');
        $messages = Message::where('conversation_id', $conversation->id)
            ->with(['sender', 'receiver', 'product', 'order'])
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return view('chat.show', compact('otherUser', 'messages', 'conversation'));
    }

    /**
     * Envoyer un message
     */
    public function send(Request $request, Conversation $conversation): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'product_id' => 'nullable|exists:products,id',
            'order_id' => 'nullable|exists:orders,id',
            'type' => 'in:text,image,file',
        ]);

        $sender = Auth::user();
        
        // Déterminer le destinataire
        $receiver = $conversation->getOtherParticipant($sender->id);

        // Vérifier si l'utilisateur peut discuter avec cette personne
        if (!$this->canChatWith($sender, $receiver)) {
            return back()->with('error', 'Vous ne pouvez pas discuter avec cet utilisateur');
        }

        // Créer le message avec conversation_id
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'content' => $request->content,
            'product_id' => $request->product_id,
            'order_id' => $request->order_id,
            'type' => $request->type ?? 'text',
        ]);
        
        // Envoyer la notification au destinataire
        Notification::send($receiver, new NewMessageNotification(
            conversationId: $message->id,
            senderName: $sender->name,
            message: $message->content,
            senderId: $sender->id
        ));
        
        // Émettre l'événement de diffusion pour le temps réel
        broadcast(new \App\Events\MessageSent($message))->toOthers();
        
        // Redirection classique vers la conversation
        return redirect()->route('chat.show', $conversation->id);
    }

    /**
     * Démarrer une conversation depuis un produit
     */
    public function startFromProduct(Product $product)
    {
        $currentUser = Auth::user();
        
        // Le client peut discuter avec le vendeur du produit
        if ($currentUser->role === 'client') {
            $vendor = $product->vendor->user;
            
            // Vérifier si une conversation existe déjà
            $conversation = Conversation::where('user_id', $currentUser->id)
                ->where('vendor_id', $vendor->id)
                ->first();
            
            // Si non, la créer
            if (!$conversation) {
                $conversation = Conversation::create([
                    'user_id' => $currentUser->id,
                    'vendor_id' => $vendor->id,
                ]);
            }
        } else {
            abort(403, 'Seuls les clients peuvent initier une conversation depuis un produit');
        }

        return redirect()->route('chat.show', $conversation);
    }

    /**
     * Démarrer une conversation depuis une commande
     */
    public function startFromOrder(Order $order)
    {
        $currentUser = Auth::user();
        
        // Déterminer l'autre participant
        if ($currentUser->role === 'client') {
            $otherUser = $order->vendor->user;
        } elseif ($currentUser->role === 'vendeur') {
            $otherUser = $order->user;
        } else {
            abort(403, 'Action non autorisée');
        }

        // Vérifier si une conversation existe déjà
        $conversation = Conversation::where(function($query) use ($currentUser, $otherUser) {
            $query->where('user_id', $currentUser->id)
                  ->where('vendor_id', $otherUser->id)
                  ->orWhere('user_id', $otherUser->id)
                  ->where('vendor_id', $currentUser->id);
        })->first();
        
        // Si non, la créer
        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => $currentUser->role === 'client' ? $currentUser->id : $otherUser->id,
                'vendor_id' => $currentUser->role === 'vendeur' ? $currentUser->id : $otherUser->id,
            ]);
        }

        return redirect()->route('chat.show', $conversation);
    }

    /**
     * Marquer un message comme lu
     */
    public function markAsRead(Message $message): JsonResponse
    {
        if ($message->receiver_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $message->markAsRead();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Supprimer une conversation
     */
    public function delete(Conversation $conversation): JsonResponse
    {
        $currentUser = Auth::user();
        
        // Vérifier si l'utilisateur peut accéder à cette conversation
        $otherUser = $conversation->getOtherParticipant($currentUser->id);
        
        if (!$this->canChatWith($currentUser, $otherUser)) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }
        
        // Supprimer tous les messages de la conversation
        Message::where('conversation_id', $conversation->id)->delete();
        
        // Supprimer la conversation
        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversation supprimée avec succès'
        ]);
    }

    /**
     * Vérifier si deux utilisateurs peuvent discuter
     */
    private function canChatWith(User $currentUser, User $otherUser): bool
    {
        // Un admin peut discuter avec tout le monde
        if ($currentUser->role === 'admin') {
            return true;
        }

        // Un client peut uniquement discuter avec les vendeurs
        if ($currentUser->role === 'client') {
            return $otherUser->role === 'vendeur';
        }

        // Un vendeur peut uniquement discuter avec les clients
        if ($currentUser->role === 'vendeur') {
            return $otherUser->role === 'client';
        }

        return false;
    }

    /**
     * Rechercher des conversations
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $currentUser = Auth::user();
        $query = $request->query;

        $conversationsQuery = Message::where(function ($q) use ($currentUser) {
                $q->where('sender_id', $currentUser->id)
                  ->orWhere('receiver_id', $currentUser->id);
            });

        // Filtrer selon le rôle pour ne rechercher que les conversations client-vendeur
        if ($currentUser->role === 'client') {
            // Client ne recherche que les vendeurs
            $conversationsQuery->where(function ($query) use ($currentUser) {
                $query->where('sender_id', $currentUser->id)
                      ->whereHas('receiver', function ($q) {
                          $q->where('role', 'vendeur');
                      })
                      ->orWhere('receiver_id', $currentUser->id)
                      ->whereHas('sender', function ($q) {
                          $q->where('role', 'vendeur');
                      });
            });
        } elseif ($currentUser->role === 'vendeur') {
            // Vendeur ne recherche que les clients
            $conversationsQuery->where(function ($query) use ($currentUser) {
                $query->where('sender_id', $currentUser->id)
                      ->whereHas('receiver', function ($q) {
                          $q->where('role', 'client');
                      })
                      ->orWhere('receiver_id', $currentUser->id)
                      ->whereHas('sender', function ($q) {
                          $q->where('role', 'client');
                      });
            });
        }

        $conversations = $conversationsQuery
            ->whereHas('sender', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->orWhereHas('receiver', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique(function ($message) use ($currentUser) {
                return $message->sender_id === $currentUser->id ? $message->receiver_id : $message->sender_id;
            })
            ->take(10);

        return response()->json([
            'conversations' => $conversations,
        ]);
    }
}

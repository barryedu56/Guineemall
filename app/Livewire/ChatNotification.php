<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class ChatNotification extends Component
{
    public $unreadCount = 0;
    public $unreadMessages = [];
    public $listening = true;
    public $showNotifications = false;

    protected $listeners = [
        'messageReceived' => 'refreshNotifications',
        'messageRead' => 'refreshNotifications',
    ];

    public function mount()
    {
        $this->refreshNotifications();
    }

    public function refreshNotifications()
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        
        $this->unreadMessages = Message::where('receiver_id', $user->id)
            ->where('is_read', false)
            ->with(['sender', 'product', 'order'])
            ->orderBy('created_at', 'desc')
            ->get();

        $this->unreadCount = $this->unreadMessages->count();
    }

    public function markAsRead($messageId)
    {
        $message = Message::find($messageId);
        
        if ($message && $message->receiver_id === Auth::id()) {
            $message->markAsRead();
            $this->refreshNotifications();
            $this->dispatch('messageRead');
        }
    }

    public function markAllAsRead()
    {
        $user = Auth::user();
        
        Message::where('receiver_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        $this->refreshNotifications();
        $this->dispatch('messageRead');
    }

    public function openChat($userId)
    {
        return redirect()->route('chat.show', $userId);
    }

    public function render()
    {
        return view('livewire.chat-notification');
    }
}

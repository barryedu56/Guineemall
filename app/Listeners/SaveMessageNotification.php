<?php

namespace App\Listeners;

use App\Events\MessageSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SaveMessageNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  \App\Events\MessageSent  $event
     * @return void
     */
    public function handle(MessageSent $event)
    {
        // Log pour le débogage
        Log::info('Message broadcasted', [
            'message_id' => $event->message->id,
            'sender' => $event->message->sender->name,
            'receiver' => $event->message->receiver->name,
            'channel' => 'conversation.' . $event->message->id,
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @param  \App\Events\MessageSent  $event
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(MessageSent $event, $exception)
    {
        Log::error('Failed to broadcast message', [
            'message_id' => $event->message->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

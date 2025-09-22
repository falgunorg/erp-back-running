<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;

class MessageSent implements ShouldBroadcastNow {

    use Dispatchable,
        InteractsWithSockets,
        SerializesModels;

    public $message;

    public function __construct(Message $message) {

        $this->message = $message;
    }

    public function broadcastOn() {
        return new PrivateChannel('conversation.' . $this->message->conversation_id);
    }

    public function broadcastWith() {
        return ['message' => $this->message];
    }

}

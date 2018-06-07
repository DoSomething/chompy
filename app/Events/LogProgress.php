<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class LogProgress implements ShouldBroadcastNow
{
    // TODO - Add comments.
    public $message;

    public $type;

    public $progressValue;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($message, $type, $progressValue = null)
    {
        $this->message = $message;
        $this->type = $type;
        $this->progressValue = $progressValue;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('importer');
    }
}

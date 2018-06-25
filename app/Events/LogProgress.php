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
    /*
     * The message being sent out.
     */
    public $message;

    /*
     * The "type" of message i.e `general` message or an updated `progress` value.
     */
    public $type;

    /*
     * An integer value that defines the progress so far (out of 100)
     */
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

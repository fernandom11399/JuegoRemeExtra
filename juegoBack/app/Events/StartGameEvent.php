<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StartGameEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $gameId;
    public function __construct($gameId)
    {
        $this->gameId = $gameId;
    }
    public function broadcastOn(): array
    {
        return [
            new Channel('start-game'),
        ];
    }
    public function broadcastAs()
    {
        return 'start-game-event';
    }
}

<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GamesEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $games;
    
    public function __construct($games)
    {
        $this->games = $games;
    }
    public function broadcastOn(): array
    {
        return [
            new Channel('games-game'),
        ];
    }

    public function broadcastAs()
    {
        return 'games-event';
    }
}

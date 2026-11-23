<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class TareaEliminada implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(public int $tareaId) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('tablero');
    }
}

<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class TareaMovida implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public array $tarea) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('tablero');
    }
}

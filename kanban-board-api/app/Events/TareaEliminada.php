<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class TareaEliminada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public int $tareaId, public ?string $eliminadaPor = null) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('tablero');
    }
}

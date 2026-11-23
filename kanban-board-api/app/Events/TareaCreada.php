<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class TareaCreada implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(public array $tarea) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('tablero');
    }
}

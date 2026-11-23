<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tablero', fn (User $user): bool => true);

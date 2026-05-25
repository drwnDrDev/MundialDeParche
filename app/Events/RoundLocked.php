<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class RoundLocked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public readonly string $roundName) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('quinela')];
    }

    public function broadcastWith(): array
    {
        return ['round' => $this->roundName];
    }
}

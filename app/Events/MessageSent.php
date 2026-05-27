<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly int     $messageId,
        public readonly int     $userId,
        public readonly string  $userName,
        public readonly ?string $userAvatar,
        public readonly string  $content,
        public readonly string  $createdAt,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('quinela')];
    }

    public function broadcastWith(): array
    {
        return [
            'id'          => $this->messageId,
            'user_id'     => $this->userId,
            'user_name'   => $this->userName,
            'user_avatar' => $this->userAvatar,
            'content'     => $this->content,
            'created_at'  => $this->createdAt,
        ];
    }
}

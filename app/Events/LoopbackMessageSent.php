<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class LoopbackMessageSent implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $source,
        public readonly string $word,
        public readonly string $timestamp,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('loopback')];
    }

    public function broadcastAs(): string
    {
        return 'loopback.message';
    }

    public function broadcastConnection(): string
    {
        return 'loopback';
    }
}

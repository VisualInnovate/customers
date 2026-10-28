<?php

namespace App\Events;

use App\Models\Call;
use App\Models\Organization;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateCallEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Call $call) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('calls.ch' . $this->call->organization_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->call->call_id,
            'session_id' => $this->call->session_id,
            'status' => $this->call->status,
        ];
    }
}

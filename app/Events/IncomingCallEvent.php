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

class IncomingCallEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Call $call) {}

    public function broadcastOn()
    {
        try {
            return new Channel('calls.ch' . $this->call->organization_id);
        } catch (\Exception $e) {
            // Log the exception and prevent the event from broadcasting
            \Log::error('Failed to broadcast IncomingCallEvent: ' . $e->getMessage());
            return;
        }
    }

    public function broadcastWith()
    {
        $metadata = $this->call->metadata;
        $metadata = array_map(function($item) {
            return is_string($item) ? json_decode($item, true) ?? $item : $item;
        }, $metadata);
        return [
            'call_id' => $this->call->call_id,
            'session' => $metadata,
            'contact' => $this->call->contact?->only(['id', 'full_name', 'phone']),
        ];
    }
}

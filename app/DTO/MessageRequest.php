<?php 

namespace App\DTO;

class MessageRequest
{
    public string $message;
    public string $uuid;
    public string $type;
    public ?int $reply_to;
    public ?string $channel;

    public function __construct(
        string $message, 
        string $uuid, 
        string $type = 'text', 
        ?int $reply_to = null,
        ?string $channel = null
    ) {
        $this->message = $message;
        $this->uuid = $uuid;
        $this->type = $type;
        $this->reply_to = $reply_to;
        $this->channel = $channel;
    }
}

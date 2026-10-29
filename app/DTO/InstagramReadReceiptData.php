<?php

namespace App\DTO;

/**
 * Data transfer object for read receipt data
 */
class InstagramReadReceiptData
{
    public string $senderId;
    public string $recipientId;
    public string $imid;

    public function __construct(array $messaging)
    {
        $this->senderId = $messaging['sender']['id'];
        $this->recipientId = $messaging['recipient']['id'];
        $this->imid = $messaging['read']['mid'];
    }
}
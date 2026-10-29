<?php

namespace App\DTO;

use Illuminate\Support\Facades\Log;

/**
 * Data transfer object for Instagram message data
 */

 class InstagramMessageData
 {
     public string $senderId;
     public string $recipientId;
     public ?string $messageId;
     public ?string $text;
     public ?string $replyToMessageId;
     public bool $isEcho;
     public ?string $mediaType;
     public ?array $mediaData;
 
     /**
      * Constructs an InstagramMessageData instance.
      *
      * @param array $messaging The incoming messaging payload.
      */
     public function __construct(array $messaging)
     {
         // Use null coalescing to safely assign values.
         $this->senderId    = $messaging['sender']['id'] ?? '';
         $this->recipientId = $messaging['recipient']['id'] ?? '';
         $this->messageId   = $messaging['message']['mid'] ?? null;
         $this->text        = $messaging['message']['text'] ?? null;
         $this->replyToMessageId = $this->extractReplyTo($messaging['message'] ?? []);
         $this->isEcho      = $messaging['message']['is_echo'] ?? false;
 
         // Handle media attachments
         $this->mediaType = $this->determineMediaType($messaging['message'] ?? []);
         $this->mediaData = $this->extractMediaData($messaging['message'] ?? []);
     }
 
     /**
      * Extracts the reply-to message ID from a message payload.
      * Handles both story replies and regular replies.
      *
      * @param array $message
      * @return string|null
      */
     private function extractReplyTo(array $message): ?string
     {
         if (isset($message['reply_to'])) {
             // For replies to a story
             if (isset($message['reply_to']['story']['id'])) {
                 return $message['reply_to']['story']['id'];
             }
             // For replies to a message
             if (isset($message['reply_to']['mid'])) {
                 return $message['reply_to']['mid'];
             }
         }
         return null;
     }
 
     /**
      * Determines the media type of the message based on attachments.
      * It returns the type of the first attachment if it is a supported type.
      *
      * @param array $message
      * @return string|null
      */
     private function determineMediaType(array $message): ?string
     {
         $supportedTypes = ['image', 'video', 'audio', 'file', 'sticker'];
 
         if (!empty($message['attachments']) && is_array($message['attachments'])) {
             // Check the first attachment for a supported type.
             $firstAttachment = $message['attachments'][0];
             if (isset($firstAttachment['type']) && in_array($firstAttachment['type'], $supportedTypes, true)) {
                 return $firstAttachment['type'];
             }
         }
 
         return null;
     }
 
     /**
      * Extracts media data from all attachments in the message.
      * Returns an array of media data arrays or null if there are no attachments.
      *
      * @param array $message
      * @return array|null
      */
     private function extractMediaData(array $message): ?array
     {
         if (empty($message['attachments']) || !is_array($message['attachments'])) {
             return null;
         }
 
         $mediaData = [];
 
         foreach ($message['attachments'] as $attachment) {
             // Get the attachment URL and attempt to extract an asset_id from it.
             $url = $attachment['payload']['url'] ?? null;
             $assetId = null;
             if ($url) {
                 parse_str(parse_url($url, PHP_URL_QUERY), $params);
                 $assetId = $params['asset_id'] ?? null;
             }
 
             $mediaData[] = [
                 'id'   => $assetId,
                 'url'  => $url,
                 'name' => $attachment['payload']['title'] ?? null,
                 'type' => $attachment['type'] ?? null,
             ];
         }
 
         return $mediaData;
     }
 }
 

 
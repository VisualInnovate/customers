<?php

namespace App\Observers;

use App\Models\Chat;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatObserver
{
    /**
     * Handle the Chat "created" event.
     */
    public function created(Chat $chat): void
    {
        $this->updateUnreadCount($chat);
    }

    /**
     * Handle the Chat "updated" event.
     */
    public function updated(Chat $chat): void
    {
        // If is_read or deleted_at changed, update the count
        if ($chat->isDirty(['is_read', 'deleted_at', 'type', 'status'])) {
            $this->updateUnreadCount($chat);
        }
    }

    /**
     * Handle the Chat "deleted" event (soft delete).
     */
    public function deleted(Chat $chat): void
    {
        $this->updateUnreadCount($chat);
    }

    /**
     * Update the unread_count for a contact.
     */
    private function updateUnreadCount(Chat $chat): void
    {
        try {
            $contact = $chat->contact;
            $unread_cnt = $chat->type == 'inbound' ? $contact->unread_count + 1 : 0;

            $contact->update(['unread_count' => $unread_cnt]);
        } catch (\Exception $e) {
            Log::error('Error updating unread count for contact ' . $contact->id . ': ' . $e->getMessage());
        }
    }
}

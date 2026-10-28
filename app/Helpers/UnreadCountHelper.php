<?php

namespace App\Helpers;

use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnreadCountHelper
{
    /**
     * Increment unread count when a new unread inbound message is received.
     *
     * @param int $contactId
     * @return void
     */
    public static function incrementUnreadCount(int $contactId): void
    {
        try {
            DB::table('contacts')
                ->where('id', $contactId)
                ->increment('unread_count');
        } catch (\Exception $e) {
            Log::error('Error incrementing unread count for contact ' . $contactId . ': ' . $e->getMessage());
        }
    }

    /**
     * Decrement unread count when a message is marked as read.
     *
     * @param int $contactId
     * @param int $count Number of messages marked as read (default 1)
     * @return void
     */
    public static function decrementUnreadCount(int $contactId, int $count = 1): void
    {
        try {
            DB::table('contacts')
                ->where('id', $contactId)
                ->where('unread_count', '>', 0)
                ->decrement('unread_count', $count);
        } catch (\Exception $e) {
            Log::error('Error decrementing unread count for contact ' . $contactId . ': ' . $e->getMessage());
        }
    }

    /**
     * Reset unread count to zero for a contact (when they send an outbound message).
     *
     * @param int $contactId
     * @return void
     */
    public static function resetUnreadCount(int $contactId): void
    {
        try {
            DB::table('contacts')
                ->where('id', $contactId)
                ->update(['unread_count' => 0]);
        } catch (\Exception $e) {
            Log::error('Error resetting unread count for contact ' . $contactId . ': ' . $e->getMessage());
        }
    }

    /**
     * Recalculate unread count from scratch (for backfill or corrections).
     *
     * @param int $contactId
     * @return void
     */
    public static function recalculateUnreadCount(int $contactId): void
    {
        try {
            $unreadCount = DB::table('chats')
                ->where('contact_id', $contactId)
                ->where('type', 'inbound')
                ->where(function ($query) {
                    $query->whereNull('is_read')
                        ->orWhere('is_read', 0);
                })
                ->where('status', '!=', 'read')
                ->whereNull('deleted_at')
                ->count();

            DB::table('contacts')
                ->where('id', $contactId)
                ->update(['unread_count' => $unreadCount]);
        } catch (\Exception $e) {
            Log::error('Error recalculating unread count for contact ' . $contactId . ': ' . $e->getMessage());
        }
    }
}

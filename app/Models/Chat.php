<?php

namespace App\Models;

use App\Helpers\DateTimeHelper;
use App\Http\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model {
    use HasFactory;
    use HasUuid;

    protected $guarded = [];
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($chat) {
            $contact = $chat->contact;
            if ($contact) {
                $contact->latest_chat_created_at = $chat->created_at;
                $contact->save();
            }
        });

        // Handle soft deletes - update the contact's latest_chat_created_at when a chat is deleted
        static::updated(function ($chat) {
            // Check if the chat was soft deleted (deleted_at changed from null to a timestamp)
            if ($chat->isDirty('deleted_at') && $chat->deleted_at !== null) {
                $contact = $chat->contact;
                if ($contact) {
                    // Find the latest non-deleted chat for this contact
                    $latestChat = Chat::where('contact_id', $contact->id)
                        ->where('organization_id', $chat->organization_id)
                        ->whereNull('deleted_at')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    $contact->latest_chat_created_at = $latestChat?->created_at;
                    $contact->save();
                }
            }
        });
    }
    
    public function getCreatedAtAttribute($value)
    {
        return DateTimeHelper::convertToOrganizationTimezone($value)->toDateTimeString();
    }
    
    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function media()
    {
        return $this->belongsTo(ChatMedia::class, 'media_id', 'id');
    }

    public function logs()
    {
        return $this->hasMany(ChatStatusLog::class, 'chat_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getReplyToAttribute()
    {
        $metadata = json_decode($this->metadata, true);
        $replyTo = null;
        if (isset($metadata['context']['id'])) {
            $replyWamId = $metadata['context']['id'];
            $original = \App\Models\Chat::where('wam_id', $replyWamId)
                ->whereNull('deleted_at')
                ->where('organization_id', $this->organization_id)
                ->with('contact', 'user', 'media')->first();
            if (!$original) {
                return null; // No original message found
            }

            if ($original->type === 'inbound') {
                // Message from customer
                $senderName = $original->contact ? $original->contact->full_name : null;
            } else {
                // Message from agent
                $senderName = 'You';
            }
            if ($original) {
                $replyTo = $original;
                $replyTo['senderName'] = $senderName;
            }
        }
        return $replyTo;
    }

    public function scopeUnread(Builder $query)
    {
        return self::applyUnreadFilter($query);
    }

    // Static reusable function
    public static function applyUnreadFilter(Builder $query): Builder
    {
        return $query->where('type', 'inbound')
            ->where(function (Builder $query) {
                $query->whereNull('is_read')
                    ->orWhere('is_read', '=', 0);
            })
            ->where('status', '!=', 'read')
            ->whereNull('deleted_at');
    }

    public static function unread($query)
    {

    }
}

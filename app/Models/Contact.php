<?php

namespace App\Models;

use App\Helpers\DateTimeHelper;
use App\Http\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Propaganistas\LaravelPhone\PhoneNumber;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model {
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'organization_id',
        'created_by',
        'updated_at',
        'deleted_at'
    ];

    protected $guarded = [];
    protected $appends = ['full_name', 'formatted_phone_number'];
    protected $dates = ['deleted_at'];
    public $timestamps = false;

    public function getCreatedAtAttribute($value)
    {
        return DateTimeHelper::convertToOrganizationTimezone($value)->toDateTimeString();
    }

    public function getUpdatedAtAttribute($value)
    {
        return DateTimeHelper::convertToOrganizationTimezone($value)->toDateTimeString();
    }

    public function getAllContacts($organizationId, $searchTerm, $group_id = null, $exclude = false)
    {
        $group_contacts_query = null;
        if(!empty($group_id)) {
            $group_contacts_query = DB::table('contact_contact_group')
                ->select(DB::raw(1))
                ->whereColumn('contact_contact_group.contact_id', 'contacts.id')
                ->where('contact_contact_group.contact_group_id', $group_id);
        }
        return $this->with(['contactGroups', 'tags'])
            ->where('organization_id', $organizationId)
            ->where('deleted_at', null)
            ->where(function ($query) use ($searchTerm) {
                $query->where('contacts.first_name', 'like', '%' . $searchTerm . '%')
                ->orWhere('contacts.last_name', 'like', '%' . $searchTerm . '%')
                
                // Split the search term into parts and check for matches in both columns
                ->orWhere(function ($subQuery) use ($searchTerm) {
                    $searchParts = explode(' ', $searchTerm);
                    if (count($searchParts) > 1) {
                        $subQuery->where('contacts.first_name', 'like', '%' . $searchParts[0] . '%')
                                ->where('contacts.last_name', 'like', '%' . $searchParts[1] . '%');
                    }
                })
                
                // Match phone or email
                ->orWhere('contacts.phone', 'like', '%' . $searchTerm . '%')
                ->orWhere('contacts.email', 'like', '%' . $searchTerm . '%');
            })
            ->when(!empty($group_contacts_query), function ($query) use ($exclude, $group_contacts_query) {
                $exclude
                    ? $query->whereNotExists($group_contacts_query)
                    : $query->whereExists($group_contacts_query);
            })
            ->orderByDesc('is_favorite')
            ->latest()
            ->orderBy('id')
            ->paginate(10);
    }

    public function getAllContactGroups($organizationId)
    {
        return ContactGroup::where('organization_id', $organizationId)->whereNull('deleted_at')->get();
    }

    public function countContacts($organizationId)
    {
        return $this->where('organization_id', $organizationId)->whereNull('deleted_at')->count();
    }

    public function contactGroups()
    {
        return $this->belongsToMany(ContactGroup::class, 'contact_contact_group', 'contact_id', 'contact_group_id')
            ->using(ContactContactGroup::class)
            ->withTimestamps();
    }

    public function notes()
    {
        return $this->hasMany(ChatNote::class, 'contact_id')->orderBy('created_at', 'desc');
    }

    public function chats()
    {
        return $this->hasMany(Chat::class, 'contact_id')->orderBy('created_at', 'asc');
    }

    public function lastChat()
    {
        return $this->hasOne(Chat::class, 'contact_id')->with('media')->ofMany('created_at', 'max');
    }

    public function lastInboundChat()
    {
        return $this->hasOne(Chat::class, 'contact_id')
                    ->where('type', 'inbound')
                    ->with('media')
                    ->latest();
    }

    public function chatLogs()
    {
        return $this->hasMany(ChatLog::class);
    }

    /**
     * Build base query for contacts with chats
     * Shared by both pagination and count methods to avoid duplication
     */
    private function buildContactsWithChatsQuery($organizationId, $searchTerm = null, $ticketingActive = false, $status = null, $role = 'owner', $allowAgentsViewAllChats = true)
    {
        $query = $this->newQuery()
            ->where('contacts.organization_id', $organizationId)
            ->whereNotNull('contacts.latest_chat_created_at')
            ->whereNull('contacts.deleted_at')
            ->select('contacts.*');

        // Filter by unread status using cached unread_count column (no need for whereHas)
        if ($status == 'unread') {
            $query->where('contacts.unread_count', '>', 0);
        }

        // Apply ticketing conditions if active
        if ($ticketingActive) {
            $query->leftJoin('chat_tickets', 'contacts.id', '=', 'chat_tickets.contact_id');

            if ($status === 'unassigned') {
                $query->whereNull('chat_tickets.assigned_to');
            } elseif ($status !== null && $status !== 'all' && $status !== 'unread') {
                $query->where('chat_tickets.status', $status);
            }

            // Apply agent permissions
            if ($role === 'agent' && !$allowAgentsViewAllChats) {
                $query->where('chat_tickets.assigned_to', auth()->user()->id);
            }
        }

        // Search filter with optimized full name search
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('contacts.first_name', 'like', "%$searchTerm%")
                    ->orWhere('contacts.last_name', 'like', "%$searchTerm%")
                    ->orWhereRaw("CONCAT(contacts.first_name, ' ', contacts.last_name) LIKE ?", ["%$searchTerm%"])
                    ->orWhere('contacts.phone', 'like', "%$searchTerm%")
                    ->orWhere('contacts.email', 'like', "%$searchTerm%");
            });
        }

        return $query;
    }

    public function contactsWithChats($organizationId, $searchTerm = null, $ticketingActive = false, $status = null, $sortDirection = 'desc', $role = 'owner', $allowAgentsViewAllChats = true)
    {
        $query = $this->buildContactsWithChatsQuery($organizationId, $searchTerm, $ticketingActive, $status, $role, $allowAgentsViewAllChats);

        // Add relationships only for paginated results
        $query->with(['lastChat', 'tags']);

        // Order by latest chat
        $query->orderBy('contacts.latest_chat_created_at', $sortDirection);

        // Paginate contacts
        return $query->paginate(10);
    }

    public function contactsWithChatsCount($organizationId, $searchTerm = null, $ticketingActive = false, $status = null, $sortDirection = 'desc', $role = 'owner', $allowAgentsViewAllChats = true)
    {
        $query = $this->buildContactsWithChatsQuery($organizationId, $searchTerm, $ticketingActive, $status, $role, $allowAgentsViewAllChats);

        // No need for relationships or ordering for count
        return $query->count();
    }

    public function getFirstNameAttribute()
    {
        $firstName = $this->attributes['first_name'];
        $firstName = $this->decodeUnicodeBytes($firstName);

        return $firstName;
    }

    public function getLastNameAttribute()
    {
        $lastName = $this->attributes['last_name'];
        $lastName = $this->decodeUnicodeBytes($lastName);

        return $lastName;
    }

    public function getFullNameAttribute()
    {
        $firstName = $this->attributes['first_name'];
        $lastName = $this->attributes['last_name'];

        // Convert byte sequences to Unicode characters
        $firstName = $this->decodeUnicodeBytes($firstName);
        $lastName = $this->decodeUnicodeBytes($lastName);

        // Return the full name combining first name and last name
        return $firstName . ' ' . $lastName;

        //return "{$this->first_name} {$this->last_name}";
    }

    public function getFormattedPhoneNumberAttribute($value)
    {
        // Use the phone() helper function to format the phone number to international format
        return phone($this->phone)->formatInternational();
    }

    protected function decodeUnicodeBytes($value)
    {
        return preg_replace_callback('/\\\\x([0-9A-F]{2})/i', function ($matches) {
            return chr(hexdec($matches[1]));
        }, $value);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'contact_tags');
    }
}

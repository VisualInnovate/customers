<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\DateTimeHelper;
use App\Models\Chat;
use App\Models\ChatTicket;
use App\Models\ChatNote;
use Carbon\Carbon;

class ChatLog extends Model
{
    use HasFactory;

    protected $guarded = [];
    public $timestamps = false;

    // Accessor to format created_at with organization's timezone
    public function getCreatedAtAttribute($value)
    {
        // Convert the stored UTC timestamp to the organization's timezone
        return DateTimeHelper::convertToOrganizationTimezone($value)->toDateTimeString();
    }

    public function getUpdatedAtAttribute($value)
    {
        return DateTimeHelper::convertToOrganizationTimezone($value)->toDateTimeString();
    }

    public function entity()
    {
        return $this->morphTo('entity');
    }

    public function getRelatedEntitiesAttribute()
    {
        $entityType = $this->entity_type;
        $entityId = $this->entity_id;
        $relatedEntity = null;

        switch ($entityType) {
            case 'chat':
                $relatedEntity = Chat::where('organization_id', 23)->with('media', 'user', 'logs')->find($entityId);
                break;
            case 'ticket':
                $relatedEntity = ChatTicketLog::find($entityId);
                break;
            case 'notes':
                $relatedEntity = ChatNote::find($entityId);
                break;
        }

        // if ($relatedEntity) {
        //     // Convert created_at timestamp to the desired timezone
        //     $relatedEntity->created_at = DateTimeHelper::convertToOrganizationTimezone($relatedEntity->created_at);
        // }

        return $relatedEntity;
    }

    public function getRelatedEntitiesWithOrgId($organizationId)
    {
        $entityType = $this->entity_type;
        $entityId = $this->entity_id;
        $relatedEntity = null;

        switch ($entityType) {
            case 'chat':
                $relatedEntity = Chat::where('organization_id', $organizationId)
                    ->with(['media', 'user', 'logs'])
                    ->find($entityId);
                break;
            case 'ticket':
                $relatedEntity = ChatTicketLog::find($entityId);
                break;
            case 'notes':
                $relatedEntity = ChatNote::find($entityId);
                break;
        }

        return $relatedEntity;
    }
}

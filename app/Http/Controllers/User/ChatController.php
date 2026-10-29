<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Resources\ChatTemplateResource;
use App\Http\Resources\ContactMediaResource;
use App\Models\AutoReply;
use App\Models\Chat;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\Organization;
use App\Models\Template;
use App\Services\ChatService;
use App\Services\WhatsappService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class ChatController extends BaseController
{
    private function chatService()
    {
        return new ChatService(session()->get('current_organization'));
    }

    public function index(Request $request, $uuid = null)
    {
        return $this->chatService()->getChatList($request, $uuid, $request->query('search'), $request->status, $request->channel);
    }

    public function ChatTemplateMessage(Request $request)
    {
        $templates = Template::where('organization_id', session()->get('current_organization'))
            ->where('deleted_at', null)
            ->where('status', 'APPROVED')
            ->select('id', 'uuid', 'name')
            ->get();

        $contacts = Contact::where('organization_id', session()->get('current_organization'))
            ->where('deleted_at', null)
            ->select('id', 'uuid', 'first_name', 'last_name', 'phone')
            ->limit(50)
            ->get();

        return Inertia::render('User/Chat/TemplateMessage', ['contacts' => $contacts, 'templates' => $templates]);
    }

    public function searchContacts(Request $request)
    {
        $search = $request->search;
        $perPage = $request->per_page ?? 20; // Configurable page size

        // Validate and sanitize the search input
        if (empty($search) || strlen($search) < 2) {
            return response()->json([
                'data' => [],
                'total' => 0,
                'current_page' => 1,
                'last_page' => 1
            ]);
        }

        $search = mb_strtolower(trim($search));
        $searchTerms = explode(' ', $search);

        $query = Contact::where('organization_id', session()->get('current_organization'))
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $query->where(function ($q) use ($term) {
                        // Search in individual columns
                        $q->where(DB::raw('LOWER(first_name)'), 'like', '%' . $term . '%')
                            ->orWhere(DB::raw('LOWER(last_name)'), 'like', '%' . $term . '%')
                            ->orWhere(DB::raw('LOWER(phone)'), 'like', '%' . $term . '%')  // phone search
                            // Concatenated name search
                            ->orWhereRaw("LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?", ['%' . $term . '%'])
                            ->orWhereRaw("LOWER(CONCAT(last_name, ' ', first_name)) LIKE ?", ['%' . $term . '%']);
                    });
                }
            })
            ->select('id', 'uuid', 'first_name', 'last_name', 'email', 'phone', 'contact_group_id', 'created_at', 'updated_at')
            ->orderBy('first_name');

        // Use Laravel's built-in pagination
        $contacts = $query->paginate($perPage);

        return response()->json([
            'data' => $contacts->items(),
            'total' => $contacts->total(),
            'current_page' => $contacts->currentPage(),
            'last_page' => $contacts->lastPage(),
            'per_page' => $contacts->perPage(),
        ]);
    }


    public function updateChatSortDirection(Request $request)
    {
        $request->session()->put('chat_sort_direction', $request->sort);

        return Redirect::back();
    }

    public function sendMessage(Request $request)
    {
        return $this->chatService()->sendMessage($request);
    }

    public function sendTemplateMessage(Request $request, $uuid)
    {
        $res = $this->chatService()->sendTemplateMessage($request, $uuid);

        return Redirect::back()->with(
            'status',
            [
                'type' => $res->success === true ? 'success' : 'error',
                'message' => $res->success === true ? __('Message sent successfully!') : $res->message,
                'res' => $res
            ]
        );
    }

    public function deleteChats($uuid)
    {
        $this->chatService()->clearContactChat($uuid);

        return Redirect::back()->with(
            'status',
            [
                'type' => 'success',
                'message' => __('Chat cleared successfully!')
            ]
        );
    }

    public function toggleAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:is_pinned,is_starred',
            'chat_id' => 'required|exists:chats,id',
        ]);

        return $this->chatService()->toggleAction($request);
    }

    public function pinnedChats(Request $request)
    {
        $request->validate(['contact_id' => 'required|exists:contacts,id']);
        return $this->chatService()->getPinnedChats($request);
    }

    public function staredChats(Request $request)
    {
        $request->validate(['contact_id' => 'required|exists:contacts,id']);
        return $this->chatService()->getStaredChats($request);
    }
    public function getMediaByContact($uuid, Request $request)
    {
        $perPage = $request->per_page ?? 10;
        $type = $request->type;
        $contactId = Contact::where('uuid', $uuid)->first()?->id;

        if (!$contactId) {
            return response()->json(['error' => 'Contact not found'], 404);
        }

        // Build the query
        $query = Chat::where('contact_id', $contactId)
            ->whereNotNull('media_id')
            ->with('media:id,path,type');

        if ($type) {
            $query->whereHas('media', function ($query) use ($type) {
                $query->where('type', 'like', "$type/%");
            });
        }
        $media = $query->paginate($perPage);

        return ContactMediaResource::collection($media);
    }
}

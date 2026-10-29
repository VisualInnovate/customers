<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomTicketRequest;
use App\Http\Resources\CustomTicketResource;
use App\Models\CustomTicket;
use App\Models\CustomTicketNote;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CustomTicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = CustomTicket::with('customTicketNotes')->where('organization_id', session()->get('current_organization'))->paginate($request->input('per_page', 10));
        return Inertia::render('User/Tickets/index', ['tickets' => CustomTicketResource::collection($tickets)]);
    }

    public function getResolvedTickets(Request $request)
    {
        $resovledTickets = CustomTicket::with('customTicketNotes')->where('organization_id', session()->get('current_organization'))->where('status', 'like', 'resolved')
            ->paginate($request->input('per_page', 10));
        return Inertia::render('User/Tickets/Resolved', ['resovledTickets' => CustomTicketResource::collection($resovledTickets)]);
    }

    public function create()
    {
        $agents = Team::with('user')->where('organization_id', session()->get('current_organization'))->get();
        return Inertia::render('User/Tickets/Create', ['agents' => $agents]);
    }

    public function store(CustomTicketRequest $request)
    {
        if ($request->has('description')) {
            if (is_array($request->description) || is_object($request->description)) {
                $description = json_encode($request->description);
            } else {
                $description = json_encode([]);
            }
        } else {
            $description = json_encode([]);
        }

        $ticket = CustomTicket::create([
            'organization_id' => session()->get('current_organization'),
            'reference' => 'T-' . CustomTicket::count() + 1,
            'contact' => $request->contact,
            'subject' => $request->subject,
            'type' => $request->type,
            'service' => $request->service,
            'status' => $request->status,
            'priority' => $request->priority,
            'agent' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
            'description' => $description
        ]);

        return Redirect::route('customtickets')->with(
            'status',
            [
                'type' => 'success',
                'message' => __('Ticket created successfully')
            ]
        );
    }

    public function storeNote(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:custom_tickets,id',
            'text' => 'nullable|string',
            'file_name' => 'nullable|string',
            'file' => 'nullable|file|max:10240',
        ]);

        $customTicket = CustomTicket::findOrFail($request->ticket_id);

        $newNote = new CustomTicketNote();
        $newNote->custom_ticket_id = $customTicket->id;
        $newNote->text = $request->text;
        $newNote->file_name = $request->file_name;

        if ($request->hasFile('file')) {
            $fileContent = $request->file('file');
            $file = Storage::disk('local')->put('public', $fileContent);
            $mediaFilePath = $file;
            $mediaUrl = rtrim(config('app.url'), '/') . '/media/' . ltrim($mediaFilePath, '/');

            $newNote->path = $mediaUrl;
        }

        $newNote->save();

        return Redirect::back()->with(
            'status',
            [
                'type' => 'success',
                'message' => __('Note added successfully')
            ]
        );
    }

    public function edit($id)
    {
        $ticket = CustomTicket::with('customTicketNotes')->findOrFail($id);
        $agents = Team::with('user')->where('organization_id', session()->get('current_organization'))->get();
        return Inertia::render('User/Tickets/Edit', ['ticket' => $ticket, 'agents' => $agents]);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'contact' => 'sometimes|required|string',
            'subject' => 'sometimes|required|string',
            'type' => 'sometimes|required|string',
            'service' => 'sometimes|required|string',
            'status' => 'sometimes|required|string',
            'priority' => 'sometimes|required|string',
            'agent' => 'sometimes|required|string',
            'description' => 'sometimes|required',
        ]);

        $ticket = CustomTicket::findOrFail($id);

        if ($request->has('description')) {
            if (is_array($request->description) || is_object($request->description)) {
                $validatedData['description'] = json_encode($request->description);
            } else {
                $validatedData['description'] = json_encode([]);
            }
        }

        $ticket->update($validatedData);

        return Redirect::route('customtickets')->with(
            'status',
            [
                'type' => 'success',
                'message' => __('Ticket updated successfully')
            ]
        );
    }

    public function destroy($id)
    {
        $ticket = CustomTicket::with('customTicketNotes')->findOrFail($id);
        $ticket->delete();
        return Redirect::route('customtickets')->with(
            'status',
            [
                'type' => 'success',
                'message' => __('Ticket deleted successfully')
            ]
        );
    }
}

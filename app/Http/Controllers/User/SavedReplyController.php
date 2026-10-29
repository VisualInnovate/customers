<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\SavedReplyResource;
use App\Models\SavedReply;
use App\Services\SavedReplyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SavedReplyController extends Controller
{
    private function savedReplyService()
    {
        return new SavedReplyService(session()->get('current_organization'));
    }

    public function chatSavedReply(Request $request)
    {
        return SavedReplyResource::collection(SavedReply::where('organization_id', session()->get('current_organization'))
        ->orderBy('created_at', 'desc')
        ->paginate($request->input('per_page', 10)));
    }

      /**
     * Display create page.
     */
    public function createSavedReply()
    {
       return Inertia::render('User/SavedReply/Add');
    }
    public function editIndex($id)
    {
        $data = SavedReply::where('organization_id', session()->get('current_organization'))->find($id);
        return Inertia::render('User/SavedReply/Edit', [
            'data' => new SavedReplyResource($data)
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('User/SavedReply/Index', [
            'rows' => SavedReplyResource::collection(SavedReply::where('organization_id', session()->get('current_organization'))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 10)))
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return $this->savedReplyService()->store($request);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'media' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        return $this->savedReplyService()->update($request, $id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $reply = SavedReply::findOrFail($id);
        $reply->delete();
        return response()->json(['success' => true]);
    }
}

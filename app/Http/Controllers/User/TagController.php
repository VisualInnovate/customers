<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TagController extends Controller
{
    private function TagService()
    {
        return new TagService(session()->get('current_organization'));
    }

    public function contactTags(Request $request)
    {
        return TagResource::collection(
            Tag::where('organization_id', session()->get('current_organization'))
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 10))
        );
    }
    public function index(Request $request)
    {
        return Inertia::render('User/Settings/Tags', [
            'rows' => TagResource::collection(Tag::where('organization_id', session()->get('current_organization'))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 10)))
        ]);
    }
    
    public function store(Request $request)
    {
        return $this->TagService()->store($request);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return $this->TagService()->update($request, $id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tag = Tag::findOrFail($id);
        $tag->delete();
        return response()->json([
            'success' => true,
            'message' => 'Tag successfully deleted.',
        ]);
    }
}

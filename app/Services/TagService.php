<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Support\Facades\Storage;

class TagService
{
    private $organizationId;

    public function __construct($organizationId)
    {
        $this->organizationId = $organizationId;
    }

    public function store($request)
    {
        $request->validate([
            'tag' => 'required|string|max:100',
        ]);
    
        try {
            $tag = new Tag();
            $tag->organization_id = $this->organizationId;
            $tag->tag = $request->input('tag');
            $tag->save();
    
            return response()->json([
                'success' => true,
                'message' => 'Tag successfully created.',
                'data' => $tag,
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save tag.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update($request, $id)
    {
        $tag = Tag::where('organization_id', $this->organizationId)->findOrFail($id);

        $tag->update([
            'tag' => $request->input('tag')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'tag successfully updated.',
            'data' => $tag,
        ], 200);
    }
}
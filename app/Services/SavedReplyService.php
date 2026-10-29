<?php

namespace App\Services;

use App\Models\SavedReply;
use Illuminate\Support\Facades\Storage;

class SavedReplyService
{
    private $organizationId;

    public function __construct($organizationId)
    {
        $this->organizationId = $organizationId;
    }

    public function store($request)
    {
        // Validate the request
        $request->validate([
            'media' => 'nullable|file|max:5240',
        ]);
    
        try {
            $savedReply = new SavedReply();
            $savedReply->organization_id = $this->organizationId;
            $savedReply->user_id = auth()->id();
            $savedReply->reply = $request->reply;
    
            // if ($request->hasFile('media')) {
            //     $filePath = $request->file('media')->store('public/media');
            //     $mediaUrl = rtrim(config('app.url'), '/') . '/storage/' . ltrim($filePath, 'public/');
            //     $savedReply->path = $mediaUrl;
            // }
            $savedReply->save();
    
            return response()->json([
                'success' => true,
                'message' => 'Saved reply successfully created.',
                'data' => $savedReply,
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save reply.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update($request, $id)
    {
        // dd($request->all());
        $savedReply = SavedReply::findOrFail($id);

        $savedReply->update([
            'reply' => $request->input('reply')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Saved reply successfully updated.',
            'data' => $savedReply,
        ], 200);


        // try {
    
        //     // if ($request->hasFile('media')) {
        //     //     if ($savedReply->path) {
        //     //         Storage::delete($savedReply->path);
        //     //     }
        //     //     $filePath = $request->file('media')->store('public/media');
        //     //     $mediaUrl = rtrim(config('app.url'), '/') . '/storage/' . ltrim($filePath, 'public/');
        //     //     $savedReply->path = $mediaUrl;
        //     // }
        //     // elseif ($request->has('path')) {
        //     //     if ($request->path !== $savedReply->path) {
        //     //         if ($savedReply->path) {
        //     //             Storage::delete($savedReply->path);
        //     //         }
        //     //         $savedReply->path = $request->path;
        //     //     }
        //     // }
    
            
    
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Failed to update reply.',
        //         'error' => $e->getMessage(),
        //     ], 500);
        // }
    }
    
}
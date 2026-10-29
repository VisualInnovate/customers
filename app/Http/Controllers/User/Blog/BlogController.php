<?php

namespace App\Http\Controllers\User\Blog;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $blogs = Blog::with('images')->get();
        return Inertia::render('User/knowledge/Blog/Index', ['rows' => BlogResource::collection($blogs)]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'sub_category_id' => 'required|exists:sub_categories,id',
            'images.*' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);

        $blog = Blog::create([
            'name' => $request->name,
            'description' => $request->description,
            'sub_category_id' => $request->sub_category_id
        ]);

        if ($request->has('images')) {
            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('uploads/images', 'public');
                $blog->images()->create([
                    'path' => $imagePath,
                ]);
            }
        }

        return response()->json(['message' => 'Blog created successfully!', 'row' => new BlogResource($blog)], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'sub_category_id' => 'required|exists:sub_categories,id',
            'images.*' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);

        $blog = Blog::findOrFail($id);

        $blog->update([
            'name' => $request->name,
            'description' => $request->description,
            'sub_category_id' => $request->sub_category_id
        ]);

        if ($request->has('images')) {
            $blog->images()->each(function ($image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            });

            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('uploads/images', 'public');
                $blog->images()->create([
                    'path' => $imagePath,
                ]);
            }
        }

        return response()->json(['message' => 'Blog updated successfully!', 'row' => new BlogResource($blog)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $blog = Blog::findOrFail($id);
        $blog->images()->each(function ($image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        });
        $blog->delete();
        return response()->json(['message' => 'Blog deleted successfully!'], 200);
    }
}

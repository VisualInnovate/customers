<?php

namespace App\Http\Controllers\User\Blog;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::with('images')->get();
        return Inertia::render('Admin/Knowledge/Category/Index', ['rows' => CategoryResource::collection($categories)]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'images.*' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);
    
        $category = Category::create([
            'name' => $validated['name'],
        ]);

        if ($request->has('images')) {
            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('uploads/images', 'public');
                
                $category->images()->create([
                    'path' => $imagePath,
                ]);
            }
        }
    
        return response()->json(['message' => 'Category created successfully!', 'row' => new CategoryResource($category)], 201);
    }

    public function update(Request $request,$id)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'images.*' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);
        
        $category = Category::findOrFail($id);
        if (isset($validated['name'])) {
            $category->update(['name' => $validated['name']]);
        }
    
        if ($request->has('images')) {
            $category->images()->each(function ($image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            });

            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('uploads/images', 'public');
                
                $category->images()->create([
                    'path' => $imagePath,
                ]);
            }
        }
    
        return response()->json(['message' => 'Category updated successfully!', 'row' => new CategoryResource($category)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        $category->images()->each(function ($image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        });
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully!'], 200);
    }
}

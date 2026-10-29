<?php

namespace App\Http\Controllers\User\Blog;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubCategoryResource;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SubCategoryController extends Controller
{
        /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subcategories = SubCategory::with('images')->get();
        return Inertia::render('Admin/Knowledge/SubCategory/Index', ['rows' => SubCategoryResource::collection($subcategories)]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'images.*' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);
    
        $subcategory = SubCategory::create([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
        ]);

        if ($request->has('images')) {
            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('uploads/images', 'public');
                
                $subcategory->images()->create([
                    'path' => $imagePath,
                ]);
            }
        }
    
        return response()->json(['message' => 'SubCategory created successfully!', 'row' => new SubCategoryResource($subcategory)], 201);
    }

    public function update(Request $request,$id)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'images.*' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);
        
        $subcategory = SubCategory::findOrFail($id);
        if (isset($validated['name'])) {
            $subcategory->update(['name' => $validated['name'], 'category_id' => $validated['category_id']]);
        }
    
        if ($request->has('images')) {
            $subcategory->images()->each(function ($image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            });

            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('uploads/images', 'public');
                
                $subcategory->images()->create([
                    'path' => $imagePath,
                ]);
            }
        }
    
        return response()->json(['message' => 'SubCategory updated successfully!', 'row' => new SubCategoryResource($subcategory)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $subcategory = SubCategory::findOrFail($id);
        $subcategory->images()->each(function ($image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        });
        $subcategory->delete();
        return response()->json(['message' => 'SubCategory deleted successfully!'], 200);
    }
}

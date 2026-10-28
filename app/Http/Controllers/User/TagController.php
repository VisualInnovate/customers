<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends BaseController
{
    public function getOrganizationId()
    {
        return session()->get('current_organization');
    }

    public function index()
    {
        return Tag::where('organization_id', $this->getOrganizationId())->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tag' => 'required|string|max:255',
        ]);

        Tag::create([
            'tag' => $validated['tag'],
            'organization_id' => $this->getOrganizationId(),
        ]);

        return back()->with(
            'status', [
                'type' => 'success',
                'message' => __('Tag added successfully!')
            ]
        );
    }
}
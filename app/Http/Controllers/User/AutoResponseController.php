<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\AutoResponseResource;
use App\Models\AutoResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AutoResponseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = AutoResponse::where('organization_id', session()->get('current_organization'))->first();
        return Inertia::render('User/Automation/AutoResponse/Index', [
            'rows' => new AutoResponseResource($data)
        ]);
    }

    public function store(Request $request, $id = null)
    {
        $validatedData = $request->validate([
            'message' => 'required|string|max:65535',
            'active' => 'required',
        ]);
        $record = $id ? AutoResponse::find($id) : new AutoResponse();

        if ($id && !$record) {
            return response()->json(['error' => 'Record not found.'], 404);
        }

        $record->organization_id = session()->get('current_organization');
        $record->message = $validatedData['message'];
        $record->active = $validatedData['active'] ? 1 : 0;
        $record->save();

        return response()->json([
            'message' => $id ? 'Record updated successfully.' : 'Record created successfully.',
            'data' => $record,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $record = AutoResponse::find($id);
        if (!$record) {
            return response()->json(['error' => 'Record not found.'], 404);
        }
        $record->delete();
        return response()->json(['message' => 'Record deleted successfully.']);
    }
}

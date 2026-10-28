<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Requests\StoreSavedReply;
use App\Http\Resources\SavedReplyResource;
use App\Models\SavedReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SavedReplyController extends BaseController
{
    public function index(Request $request)
    {
        $orgId = session()->get('current_organization');
        // allow overriding organization via query param only if user is admin
        if ($request->has('organization_id') && Gate::allows('manage-global-saved-replies')) {
            $orgId = $request->query('organization_id');
        }

        $query = SavedReply::where('organization_id', $orgId);

        $scope = $request->query('scope', 'user'); // user or all
        if ($scope === 'user') {
            $query->where('user_id', Auth::id());
        }

        if ($request->query('q')) {
            $q = $request->query('q');
            $query->where('reply', 'like', "%{$q}%");
        }

        $perPage = (int) $request->query('per_page', 15);
        $rows = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return SavedReplyResource::collection($rows);
    }

    public function chats(Request $request)
    {
        // lightweight endpoint used by SavedRepliesModal.vue
        $orgId = organization_id();
        $rows = SavedReply::where('organization_id', $orgId)
            ->orderBy('created_at', 'desc')
            ->get();

        return SavedReplyResource::collection($rows);
    }

    public function show($id)
    {
        $row = SavedReply::where('id', $id)->firstOrFail();
        // ownership check: allow if user is owner or admin of org
        if ($row->user_id && $row->user_id !== Auth::id() && !Gate::allows('manage-global-saved-replies')) {
            abort(403);
        }
        return new SavedReplyResource($row);
    }

    /**
     * Handle storing uploaded media and return Storage URL or null.
     */
    protected function handleMediaUpload(Request $request)
    {
        if (!$request->hasFile('media')) {
            return null;
        }

        $file = $request->file('media');

        // validate file types and size here
        $allowedMime = [
            'image/jpeg', 'image/png', 'image/gif',
            'video/mp4', 'video/quicktime',
            'audio/mpeg', 'audio/ogg',
            'application/pdf',
        ];

        if (!in_array($file->getMimeType(), $allowedMime)) {
            abort(422, 'Unsupported media type');
        }

        // maximum 10MB by default
        $maxBytes = 10 * 1024 * 1024;
        if ($file->getSize() > $maxBytes) {
            abort(422, 'Media too large');
        }

        $disk = config('filesystems.default', 'public');
        $folder = 'saved-replies/' . date('Y/m');
        $name = Str::random(12) . '_' . preg_replace('/[^A-Za-z0-9_.]/', '_', $file->getClientOriginalName());

        $path = $file->storeAs($folder, $name, $disk);

        // Return a full URL if Storage::url works for disk
        try {
            return Storage::disk($disk)->url($path);
        } catch (\Exception $e) {
            return $path;
        }
    }

    public function store(StoreSavedReply $request)
    {
        $data = $request->only(['organization_id', 'user_id', 'reply', 'path']);

        // If user_id present and the current user is not admin, override to current user
        if (!Gate::allows('manage-global-saved-replies')) {
            $data['user_id'] = Auth::id();
        }

        // Ensure organization matches session unless admin override
        if (!Gate::allows('manage-global-saved-replies')) {
            $data['organization_id'] = organization_id();
        }

        // If there's an uploaded media file, store it and set path to storage URL
        $mediaUrl = $this->handleMediaUpload($request);
        if ($mediaUrl) {
            $data['path'] = $mediaUrl;
        } else {
            // if path provided as a string, keep it. otherwise null
            $data['path'] = $data['path'] ?? null;
        }

        $row = SavedReply::create($data);

        return new SavedReplyResource($row);
    }

    public function update(StoreSavedReply $request, $id)
    {
        $row = SavedReply::where('id', $id)->firstOrFail();

        if ($row->user_id && $row->user_id !== Auth::id() && !Gate::allows('manage-global-saved-replies')) {
            abort(403);
        }

        $data = $request->only(['organization_id', 'user_id', 'reply', 'path']);

        // If not admin, ensure user_id stays current user and org stays session org
        if (!Gate::allows('manage-global-saved-replies')) {
            $data['user_id'] = Auth::id();
            $data['organization_id'] = organization_id();
        }

        // If media uploaded, store and overwrite path
        if ($request->hasFile('media')) {
            // Delete old media if exists and is a Storage URL
            if ($row->path) {
                // extract storage path from URL
                $storagePath = str_replace(Storage::disk(config('filesystems.default', 'public'))->url('/'), '', $row->path);
                Storage::disk(config('filesystems.default', 'public'))->delete($storagePath);
            }

            $mediaUrl = $this->handleMediaUpload($request);
            if ($mediaUrl) {
                $data['path'] = $mediaUrl;
            } else {
                // if path provided as a string keep it; otherwise do not modify existing path
                if (!array_key_exists('path', $data)) {
                    unset($data['path']);
                }
            }
        }

        $row->update($data);

        return new SavedReplyResource($row);
    }

    public function delete($id)
    {
        $row = SavedReply::where('id', $id)->firstOrFail();

        if ($row->user_id && $row->user_id !== Auth::id() && !Gate::allows('manage-global-saved-replies')) {
            abort(403);
        }

        $row->delete();

        return response()->json(null, 204);
    }
}

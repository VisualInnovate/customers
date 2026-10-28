<?php

namespace App\Services;

use App\Http\Resources\NotificationsResource;
use App\Models\OrganizationApiKey;
use Str;

class OrganizationApiService
{
    public function generate(object $request)
    {
        OrganizationApiKey::create([
            'organization_id' => session()->get('current_organization'),
            'name' => $request->name,
            'expire_at' => $request->expire_at ?? null,
            'limit' => $request->limit ?? null,
            'usage' => 0,
            'token' => Str::random(40)
        ]);
    }

    public function destroy($uuid)
    {
        OrganizationApiKey::where('uuid', $uuid)->update([
            'deleted_at' => now()
        ]);
    }
}
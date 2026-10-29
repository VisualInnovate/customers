<?php

namespace Modules\Instagram\Controllers;

use App\Http\Controllers\Controller as BaseController;

use App\Models\Addon;
use App\Models\Setting;
use Modules\EmbeddedSignup\Requests\StoreEmbeddedSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Str;

class SetupController extends BaseController
{
    public function index(Request $request){
        //
    }

    public function store(Request $request){
        $settings = $request->settings;

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert([
                'key' => $key
            ],[
                'value' => $value,
            ]);
        }

        //Add token
        $wToken = Setting::where('key', 'instagram_callback_token')->first();
        if(!$wToken){
            Setting::create([
                'key' => 'instagram_callback_token',
                'value' => now()->format('YmdHis') . Str::random(4)
            ]);
        }

        return redirect('/admin/addons')->with(
            'status', [
                'type' => 'success', 
                'message' => __('instagram settings updated successfully!')
            ]
        );
    }

    public function update(Request $request){
        $metadata = [
            "input_fields" => [
                [
                    "element" => "input",
                    "type" => "text",
                    "name" => "instagram_client_id",
                    "label" => "App ID",
                    "class" => "col-span-1"
                ],
                [
                    "element" => "input",
                    "type" => "password",
                    "name" => "instagram_client_secret",
                    "label" => "App Secret",
                    "class" => "col-span-1"
                ]
            ]
        ];

        Addon::where('uuid', $request->uuid)->update(['metadata' => json_encode($metadata)]);
    }
}



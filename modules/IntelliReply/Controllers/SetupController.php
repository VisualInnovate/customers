<?php

namespace Modules\IntelliReply\Controllers;

use App\Http\Controllers\Controller as BaseController;

use Modules\IntelliReply\Requests\StoreSettings;
use Illuminate\Support\Facades\DB;

class SetupController extends BaseController
{
    public function store(StoreSettings $request){
        $settings = $request->settings;

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert([
                'key' => $key
            ],[
                'value' => $value,
            ]);
        }

        return redirect('/admin/addons')->with(
            'status', [
                'type' => 'success', 
                'message' => __('OpenAi settings updated successfully!')
            ]
        );
    }
}
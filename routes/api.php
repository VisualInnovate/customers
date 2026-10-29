<?php

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateBearerToken;
use App\Http\Controllers\ShopFiy\shopfiyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});*/
Route::get('/translations/{locale}', function ($locale) {
    if (Str::startsWith($locale, 'php_')) {
        return response()->json(['error' => 'Invalid locale'], 400);
    }

    $path = base_path("lang/{$locale}.json");
    
    if (!File::exists($path)) {
        return response()->json(['error' => 'Translation file not found'], 404);
    }

    return response()->json(json_decode(File::get($path), true));
});


Route::post('shopfiy',[shopfiyController::class,'index']);
Route::get('go',function(){
    return 555;
});
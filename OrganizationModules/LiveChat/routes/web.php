<?php

use Illuminate\Support\Facades\Route;
use OrganizationModules\LiveChat\App\Http\Controllers\LiveChatController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group([], function () {
    Route::get('livechat/show', [LiveChatController::class, 'index']);
    Route::post('livechat', [LiveChatController::class, 'store']);
});

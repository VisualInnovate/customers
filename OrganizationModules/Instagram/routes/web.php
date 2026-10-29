<?php

use Illuminate\Support\Facades\Route;
use OrganizationModules\Instagram\App\Http\Controllers\InstagramChatController;
use OrganizationModules\Instagram\App\Http\Controllers\InstagramController;
use OrganizationModules\Instagram\App\Http\Controllers\IntsagramWebhookController;

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
    Route::get('instagram/auth', [InstagramController::class, 'redirectToInstagram']);
    Route::get('instagram/callback', [InstagramController::class, 'handleInstagramCallback'])->name('instagram.callback');
    Route::get('/instagram/delete/integration', [InstagramController::class, 'deleteIntegration']);
    
    Route::match(['get', 'post'], '/instagram/webhook/register', [IntsagramWebhookController::class, 'handle']);
    
    
    // instagram chat 
    Route::post('/instagram/send-message', [InstagramChatController::class, 'sendMessage']);

});

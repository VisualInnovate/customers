<?php 

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth:admin'])->group(function () {
    // Route::post('/addons/setup/instagram', [Modules\Instagram\Controllers\SetupController::class, 'store']);
    // Route::put('/addons/setup/instagram', [Modules\Instagram\Controllers\SetupController::class, 'update']);
});
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('addons/setup/shopify')->middleware(['auth:user'])->group(function () {
    Route::post('', [Modules\Shopify\Controllers\SetupController::class, 'store']);
    Route::put('', [Modules\Shopify\Controllers\SetupController::class, 'update']);
    Route::post('/create-template', [Modules\Shopify\Controllers\SetupController::class, 'createShopifyTemplate']);
});

Route::get('/shopify/health', function () {
    return response()->json(['status' => 'ok']);
});

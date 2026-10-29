<?php

use Illuminate\Support\Facades\Route;
use OrganizationModules\Shopify\App\Http\Controllers\ShopifyController;

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

Route::group(['middleware' => ['web']], function () {
    Route::get('get/shopify-data/{id?}', [ShopifyController::class, 'show']);
    Route::get('integrations', [ShopifyController::class, 'index'])->name('integrations');
    // Route::post('store/shopify-data', [ShopifyController::class, 'store']);

    // shopify
    Route::post('shopify/redirect', [ShopifyController::class, 'redirectToShopify'])->name('shopify.redirect');
    Route::any('webhooks/shopify/callback', [ShopifyController::class, 'handleShopifyCallback'])->name('shopify.callback');
    // Route::get('shopify/success', [ShopifyController::class, 'success'])->name('shopify.success');

    Route::post('webhooks/{organizationId}/shopify', [ShopifyController::class, 'handle'])->name('shopify.webhook');
});
<?php

use App\Http\Controllers\Api\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\User\ChatController;
use App\Models\Language;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use OrganizationModules\Shopify\App\Http\Controllers\ShopifyController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

//Installer routes
Route::get('/install/{step?}', [App\Http\Controllers\InstallerController::class, 'index'])->name('install');
Route::post('/install/configure-database', [App\Http\Controllers\InstallerController::class, 'configureDatabase']);
Route::post('/install/configure-company', [App\Http\Controllers\InstallerController::class, 'configureCompany']);
Route::post('/install/migrate', [App\Http\Controllers\InstallerController::class, 'runMigrations']);
Route::get('/update', [App\Http\Controllers\InstallerController::class, 'update'])->name('install.update');
Route::post('/update', [App\Http\Controllers\InstallerController::class, 'runUpdate']);

Route::get('/current-locale', function () {
    return response()->json(['locale' => app()->getLocale()]);
});

Route::get('/locales', function () {
    $locales = Language::all()->pluck('code');
    return response()->json($locales);
});

Route::get('/translations/{locale}', function ($locale) {
    $path = base_path("lang/{$locale}.json");
    if (File::exists($path)) {
        $translations = File::get($path);
        return response()->json(json_decode($translations, true));
    } else {
        return response()->json(['error' => 'Locale not found'], 404);
    }
});

//Frontend Routes
Route::match(['get', 'post'], '/', [App\Http\Controllers\FrontendController::class, 'index']);
Route::match(['get', 'post'], '/pages/{slug}', [App\Http\Controllers\FrontendController::class, 'pages']);
Route::match(['get', 'post'], '/privacy', [App\Http\Controllers\FrontendController::class, 'privacy']);
Route::match(['get', 'post'], '/terms-of-service', [App\Http\Controllers\FrontendController::class, 'termsOfService']);
Route::match(['get', 'post'], '/process-campaign', [App\Http\Controllers\FrontendController::class, 'buildTemplateChatMessage']);
Route::get('/language/{locale}', [App\Http\Controllers\FrontendController::class, 'changeLanguage']);

// Route::controller(ArticleController::class)->group(function () {
//     Route::get('/articles', 'index');
//     Route::get('/articles/{id}', 'show');
// });
// Route::get('/categories', [App\Http\Controllers\Api\CategoryContoller::class, 'index']);
// Route::get('/categories/{id}', [App\Http\Controllers\Api\CategoryContoller::class, 'show']);


//File Route
Route::get('media/{filename}', [App\Http\Controllers\FileController::class, 'show'])->where('filename', '.*');

//Invite Routes
Route::get('/invite/{identifier}', [App\Http\Controllers\AuthController::class, 'viewInvite']);
Route::post('/invite/{identifier}', [App\Http\Controllers\AuthController::class, 'invite']);

Route::get('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

//Webhook
Route::match(['get', 'post'], '/webhook/whatsapp/{identifier?}', [App\Http\Controllers\WebhookController::class, 'handle']);
Route::match(['get', 'post'], '/webhook/waba', [App\Http\Controllers\WebhookController::class, 'whatsappWebhook']);
Route::match(['get', 'post'], '/webhook/{processor}', [App\Http\Controllers\WebhookController::class, 'processWebhook']);
Route::match(['get', 'post'], '/payment/{processor}', [App\Http\Controllers\PaymentController::class, 'processPayment']);


// tap 
Route::post('/webhook/tap', [App\Http\Controllers\Admin\TapController::class, 'webhook'])->name('tap.webhook');
Route::get('/callback/tap', [App\Http\Controllers\Admin\TapController::class, 'callback'])->name('tap.callback');



Route::get('/campaign-send', [App\Http\Controllers\FrontendController::class, 'sendCampaign']);
Route::get('/migrate-upgrade', [App\Http\Controllers\FrontendController::class, 'migrate']);

Route::middleware(['guest', 'redirectIfAuthenticated:user,admin'])->group(function () {
    Route::get('/login', [App\Http\Controllers\AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\AuthController::class, 'login'])->name('login.submit');
    Route::get('/social-login/{type?}', [App\Http\Controllers\AuthController::class, 'socialLogin']);
    Route::get('/google/callback', [App\Http\Controllers\AuthController::class, 'googleCallback'])->name('google.callback');
    Route::get('/facebook/callback', [App\Http\Controllers\AuthController::class, 'handleFacebookCallback']);
    Route::get('/signup', [App\Http\Controllers\AuthController::class, 'showRegistrationForm']);
    Route::post('/signup', [App\Http\Controllers\AuthController::class, 'handleRegistration']);
    Route::get('/forgot-password', [App\Http\Controllers\AuthController::class, 'showForgotForm']);
    Route::post('/forgot-password', [App\Http\Controllers\AuthController::class, 'createPasswordResetToken']);
    Route::get('/reset-password', [App\Http\Controllers\AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [App\Http\Controllers\AuthController::class, 'resetPassword']);
});

Route::middleware(['auth:user'])->group(function () {
    Route::get('/email/verify', [App\Http\Controllers\AuthController::class, 'verifyEmail'])->middleware('auth')->name('verification.notice');
    
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect('/dashboard');
    })->middleware(['auth', 'signed'])->name('verification.verify');

    Route::post('/email/verification-notification', [App\Http\Controllers\AuthController::class, 'sendEmailVerification'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');

    Route::group(['middleware' => ['check.email.verification']], function () {
        Route::get('/select-organization', [App\Http\Controllers\User\OrganizationController::class, 'index'])->name('user.organization.index');
        Route::post('/organization', [App\Http\Controllers\User\OrganizationController::class, 'store'])->name('user.organization.store');

        Route::group(['middleware' => ['check.organization']], function () {

            // Route::post('/invite-user', App\Http\Controllers\Mailing\EmailController::class);

            //User Panel Routes
            Route::match(['get', 'post'], '/dashboard', [App\Http\Controllers\User\DashboardController::class, 'index'])->name('dashboard');

            Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update']);
            Route::put('/profile/password', [App\Http\Controllers\ProfileController::class, 'updatePassword']);
            Route::put('/profile/organization', [App\Http\Controllers\ProfileController::class, 'updateOrganization']);

            Route::group(['middleware' => 'check.client.role'], function () {
                Route::delete('dismiss-notification/{type}', [App\Http\Controllers\User\DashboardController::class, 'dismissNotification'])->name('dashboard.team.notification.dismiss');
                Route::match(['get', 'post'], '/billing', [App\Http\Controllers\User\BillingController::class, 'index'])->name('user.billing.index');
                Route::post('/pay', [App\Http\Controllers\User\BillingController::class, 'pay'])->name('user.billing.pay');
                Route::resource('subscription', App\Http\Controllers\User\SubscriptionController::class);
            });

            Route::group(['middleware' => 'check.subscription'], function () {
                Route::get('/chats-folders', [App\Http\Controllers\User\ChatController::class, 'chatFolders']);
                Route::get('/chats/{uuid?}', [App\Http\Controllers\User\ChatController::class, 'index']);
                Route::get('/chats/{id}/media', [App\Http\Controllers\User\ChatController::class, 'getMedia']);
                Route::post('/chats', [App\Http\Controllers\User\ChatController::class, 'sendMessage']);
                Route::delete('/chats/{uuid}', [App\Http\Controllers\User\ChatController::class, 'deleteChats']);
                Route::get('/chat/send', [App\Http\Controllers\User\ChatController::class, 'sendMessage']);
                Route::get('/chat/test/{id}', [App\Http\Controllers\User\ChatController::class, 'sendAutoReply']);
                Route::post('/chats/update-sort-direction', [App\Http\Controllers\User\ChatController::class, 'updateChatSortDirection']);
               
                // tickets
                Route::get('/tickets/index', [App\Http\Controllers\User\CustomTicketController::class, 'index'])->name('customtickets');
                Route::get('/tickets/resolved', [App\Http\Controllers\User\CustomTicketController::class, 'getResolvedTickets'])->name('resolvedtickets');
                Route::get('/tickets/create', [App\Http\Controllers\User\CustomTicketController::class, 'create']);
                Route::post('/tickets/store', [App\Http\Controllers\User\CustomTicketController::class, 'store']);
                Route::post('/tickets/note/store', [App\Http\Controllers\User\CustomTicketController::class, 'storeNote']);
                Route::get('/tickets/edit/{id}', [App\Http\Controllers\User\CustomTicketController::class, 'edit']);
                Route::post('/tickets/update/{id}', [App\Http\Controllers\User\CustomTicketController::class, 'update']);
                Route::get('/tickets/destroy/{id}', [App\Http\Controllers\User\CustomTicketController::class, 'destroy']);

                Route::get('/template/message/chat', [App\Http\Controllers\User\ChatController::class, 'ChatTemplateMessage']);
                Route::get('/template/message/contacts/search', [App\Http\Controllers\User\ChatController::class, 'searchContacts']);
                Route::post('/template/message/{uuid}', [App\Http\Controllers\User\ChatController::class, 'sendTemplateMessage']);

                Route::get('/tickets/{status}', [App\Http\Controllers\User\ChatTicketController::class, 'index']);
                Route::put('/tickets/{uuid}/update', [App\Http\Controllers\User\ChatTicketController::class, 'update']);
                Route::put('/tickets/{uuid}/assign', [App\Http\Controllers\User\ChatTicketController::class, 'assign']);

                Route::get('/contacts/{uuid?}', [App\Http\Controllers\User\ContactController::class, 'index'])->name('contacts');
                Route::post('/contacts', [App\Http\Controllers\User\ContactController::class, 'store']);
                Route::post('/contacts/import', [App\Http\Controllers\User\ContactController::class, 'import']);
                Route::post('/contacts/{uuid}', [App\Http\Controllers\User\ContactController::class, 'update']);
                Route::put('/contacts/favorite/{uuid}', [App\Http\Controllers\User\ContactController::class, 'favorite']);
                Route::delete('/contacts', [App\Http\Controllers\User\ContactController::class, 'delete']);

                Route::get('/contact-list', [App\Http\Controllers\User\ContactController::class, 'indexJson']);


                Route::get('/contact-groups/{uuid?}', [App\Http\Controllers\User\ContactGroupController::class, 'index']);
                Route::post('/contact-groups', [App\Http\Controllers\User\ContactGroupController::class, 'store']);
                Route::post('/contact-groups/import', [App\Http\Controllers\User\ContactGroupController::class, 'import']);
                Route::post('/contact-groups/{uuid}', [App\Http\Controllers\User\ContactGroupController::class, 'update']);
                Route::delete('/contact-groups', [App\Http\Controllers\User\ContactGroupController::class, 'delete']);

                Route::get('/campaigns/{uuid?}', [App\Http\Controllers\User\CampaignController::class, 'index'])->name('campaigns');
                Route::post('/campaigns', [App\Http\Controllers\User\CampaignController::class, 'store']);
                Route::get('/campaigns/export/{uuid?}', [App\Http\Controllers\User\CampaignController::class, 'export']);
                Route::delete('/campaigns/{uuid?}', [App\Http\Controllers\User\CampaignController::class, 'delete']);

                Route::match(['get', 'post'], '/templates/create', [App\Http\Controllers\User\TemplateController::class, 'create']);
                Route::get('/templates/{uuid?}', [App\Http\Controllers\User\TemplateController::class, 'index']);
                Route::post('/templates', [App\Http\Controllers\User\TemplateController::class, 'store']);
                Route::post('/templates/{uuid}', [App\Http\Controllers\User\TemplateController::class, 'update']);
                Route::delete('/templates/{uuid}', [App\Http\Controllers\User\TemplateController::class, 'delete']);

                Route::get('/automation/basic', [App\Http\Controllers\User\CannedReplyController::class, 'index'])->name('cannedReply');
                Route::get('/automation/basic/create', [App\Http\Controllers\User\CannedReplyController::class, 'create'])->name('cannedReply.create');
                Route::post('/automation/basic', [App\Http\Controllers\User\CannedReplyController::class, 'store'])->name('cannedReply.store');
                Route::get('/automation/basic/{uuid}/edit', [App\Http\Controllers\User\CannedReplyController::class, 'edit'])->name('cannedReply.edit');
                Route::put('/automation/basic/{uuid}', [App\Http\Controllers\User\CannedReplyController::class, 'update'])->name('cannedReply.update');
                Route::delete('/automation/basic/{uuid}', [App\Http\Controllers\User\CannedReplyController::class, 'delete'])->name('cannedReply.destroy');

                Route::get('/support/{uuid?}', [App\Http\Controllers\User\TicketController::class, 'index'])->name('support');
                Route::post('/support', [App\Http\Controllers\User\TicketController::class, 'store']);
                Route::post('/support/{uuid}/comment', [App\Http\Controllers\User\TicketController::class, 'comment']);
                Route::post('/support/{uuid}/status', [App\Http\Controllers\User\TicketController::class, 'changeStatus']);
                Route::post('/support/{uuid}/priority', [App\Http\Controllers\User\TicketController::class, 'changePriority']);

                Route::match(['get', 'post'], '/messages', [App\Http\Controllers\User\MessageController::class, 'index']);
                Route::match(['get', 'post'], '/message-templates', [App\Http\Controllers\User\TemplateController::class, 'index']);
                Route::match(['get', 'post'], '/instances', [App\Http\Controllers\User\InstanceController::class, 'index']);

                Route::get('/team', [App\Http\Controllers\User\TeamController::class, 'index'])->name('team'); 

                Route::group(['middleware' => 'check.client.role'], function () {
                    Route::get('/settings', [App\Http\Controllers\User\SettingController::class, 'index']);
                    Route::get('/settings/m', [App\Http\Controllers\User\SettingController::class, 'mobileView']);

                    Route::get('/settings/whatsapp', [App\Http\Controllers\User\SettingController::class, 'viewWhatsappSettings']);
                    Route::get('/settings/whatsapp/refresh', [App\Http\Controllers\User\SettingController::class, 'refreshWhatsappData']);
                    Route::post('/settings/whatsapp/token', [App\Http\Controllers\User\SettingController::class, 'updateToken']);
                    Route::post('/settings/whatsapp', [App\Http\Controllers\User\SettingController::class, 'storeWhatsappSettings']);
                    Route::post('/settings/whatsapp/business-profile', [App\Http\Controllers\User\SettingController::class, 'whatsappBusinessProfileUpdate']);
                    Route::delete('/settings/whatsapp/business-profile', [App\Http\Controllers\User\SettingController::class, 'deleteWhatsappIntegration']);
                    Route::match(['get', 'post'], '/settings/contacts', [App\Http\Controllers\User\SettingController::class, 'contacts']);
                    Route::match(['get', 'post'], '/settings/tickets', [App\Http\Controllers\User\SettingController::class, 'tickets']);
                    Route::resource('contact-fields', App\Http\Controllers\User\ContactFieldController::class);

                    Route::post('/team/invite', [App\Http\Controllers\User\TeamController::class, 'invite'])->name('team.store');
                    Route::put('/team/{uuid}', [App\Http\Controllers\User\TeamController::class, 'update'])->name('team.update');
                    Route::delete('/team/{uuid}', [App\Http\Controllers\User\TeamController::class, 'delete'])->name('team.destroy');

                    Route::get('/developer', [App\Http\Controllers\User\DeveloperController::class, 'index']);
                    Route::post('/developer', [App\Http\Controllers\User\DeveloperController::class, 'store']);
                    Route::delete('/developer/{uuid}', [App\Http\Controllers\User\DeveloperController::class, 'delete']);
                });

                //Route::get('/whatsapp/message', [App\Http\Controllers\User\WhatsappController::class, 'sendMessage']);
                Route::resource('notes', App\Http\Controllers\User\ChatNoteController::class);

                Route::post('/toggle/chats', [ChatController::class, 'toggleAction'])->name('chats.toggle');
                Route::get('/pinned/chats', [ChatController::class, 'pinnedChats']);
                Route::get('/stared/chats', [ChatController::class, 'staredChats']);
                Route::get('/contacts/media/{uuid}', [ChatController::class, 'getMediaByContact']);

                // saved replies
                Route::get('/saved-replies/chats', [App\Http\Controllers\User\SavedReplyController::class, 'chatSavedReply']);
                Route::get('/saved-replies', [App\Http\Controllers\User\SavedReplyController::class, 'index']);
                Route::get('/saved-replies/create', [App\Http\Controllers\User\SavedReplyController::class, 'createSavedReply']);
                Route::post('/saved-replies', [App\Http\Controllers\User\SavedReplyController::class, 'store']);
                Route::post('/saved-replies/update/{id}', [App\Http\Controllers\User\SavedReplyController::class, 'update']);
                Route::get('/saved-replies/edit/{id}', [App\Http\Controllers\User\SavedReplyController::class, 'editIndex']);
                Route::delete('/saved-replies/{id}', [App\Http\Controllers\User\SavedReplyController::class, 'destroy']);

                // saved replies
                Route::get('/auto-reply', [App\Http\Controllers\User\AutoResponseController::class, 'index']);
                Route::post('/auto-reply/store/{id?}', [App\Http\Controllers\User\AutoResponseController::class, 'store']);
                Route::delete('/auto-reply/{id}', [App\Http\Controllers\User\AutoResponseController::class, 'destroy']);

                // tags
                Route::get('/tags/contacts', [App\Http\Controllers\User\TagController::class, 'contactTags']);
                Route::get('/settings/tags', [App\Http\Controllers\User\TagController::class, 'index']);
                Route::post('/tags', [App\Http\Controllers\User\TagController::class, 'store']);
                Route::delete('/tags/{id}', [App\Http\Controllers\User\TagController::class, 'destroy']);

                // groups
                Route::post('/groups/add-members', [App\Http\Controllers\User\ContactGroupController::class, 'addMembers']);
                Route::post('/groups/remove-members', [App\Http\Controllers\User\ContactGroupController::class, 'removeMembers']);


            });
        });
    });
});

//Admin Panel Routes
Route::prefix('admin')->middleware(['auth:admin'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index']);
    Route::get('/organizations/{uuid}/cash-payment-quote', [App\Http\Controllers\Admin\OrganizationController::class, 'cashPaymentQuote']);
    Route::post('/organizations/{uuid}/cash-payment', [App\Http\Controllers\Admin\OrganizationController::class, 'recordCashPayment']);
    Route::resource('users', App\Http\Controllers\Admin\UserController::class);
    Route::resource('organizations', App\Http\Controllers\Admin\OrganizationController::class);
    /*Route::resource('blog/posts', App\Http\Controllers\Admin\BlogController::class);
    Route::resource('blog/categories', App\Http\Controllers\Admin\BlogCategoryController::class);
    Route::resource('blog/authors', App\Http\Controllers\Admin\BlogAuthorController::class);
    Route::resource('blog/tags', App\Http\Controllers\Admin\BlogTagController::class);*/
    Route::resource('tax-rates', App\Http\Controllers\Admin\TaxController::class);
    Route::resource('coupons', App\Http\Controllers\Admin\CouponController::class);
    Route::resource('faqs', App\Http\Controllers\Admin\FaqController::class);
    Route::resource('testimonials', App\Http\Controllers\Admin\TestimonialController::class);
    Route::resource('plans', App\Http\Controllers\Admin\SubscriptionPlanController::class);
    Route::resource('team/users', App\Http\Controllers\Admin\TeamController::class)->names([
        'index' => 'team.users.index',
        'create' => 'team.users.create',
        'store' => 'team.users.store',
        'show' => 'team.users.show',
        'edit' => 'team.users.edit',
        'update' => 'team.users.update',
        'destroy' => 'team.users.destroy',
    ]);
    Route::resource('team/roles', App\Http\Controllers\Admin\RoleController::class);
    Route::resource('billing', App\Http\Controllers\Admin\BillingController::class);
    Route::resource('addons', App\Http\Controllers\Admin\AddonController::class);
    Route::post('addons/install', [App\Http\Controllers\Admin\AddonController::class, 'install']);
    Route::post('/addons/setup/google-recaptcha', [App\Http\Controllers\Admin\AddonController::class, 'store']);
    Route::post('/addons/setup/google-analytics', [App\Http\Controllers\Admin\AddonController::class, 'store']);
    Route::post('/addons/setup/google-maps', [App\Http\Controllers\Admin\AddonController::class, 'store']);
    Route::resource('payment-gateways', App\Http\Controllers\Admin\PaymentGatewayController::class)->only(['index', 'show', 'update']);
    Route::get('/languages/{language}/export', [App\Http\Controllers\Admin\LanguageController::class, 'export']);
    Route::post('/languages/{language}/import', [App\Http\Controllers\Admin\LanguageController::class, 'import']);
    Route::get('/languages/{language}/translations', [App\Http\Controllers\Admin\LanguageController::class, 'translations']);
    Route::get('/languages/{language}/default', [App\Http\Controllers\Admin\LanguageController::class, 'setDefault']);
    Route::resource('languages', App\Http\Controllers\Admin\LanguageController::class);
    Route::post('/translations/{languageCode}/{key}', [App\Http\Controllers\Admin\LanguageController::class, 'updateTranslation']);

    /*Route::get('/pages', [App\Http\Controllers\Admin\PageController::class, 'index']);
    Route::get('/users/{uuid}/organizations', [App\Http\Controllers\Admin\CustomerController::class, 'userOrganizations']);
    Route::get('/subscriptions', [App\Http\Controllers\Admin\SubscriptionController::class, 'index']);*/
    Route::get('/payment-logs', [App\Http\Controllers\Admin\PaymentController::class, 'index']);

    Route::get('/support/{uuid?}', [App\Http\Controllers\Admin\TicketController::class, 'index'])->name('tickets');
    Route::post('/support', [App\Http\Controllers\Admin\TicketController::class, 'store']);
    Route::post('/support/{uuid}/comment', [App\Http\Controllers\Admin\TicketController::class, 'comment']);
    Route::post('/support/{uuid}/status', [App\Http\Controllers\Admin\TicketController::class, 'changeStatus']);
    Route::post('/support/{uuid}/priority', [App\Http\Controllers\Admin\TicketController::class, 'changePriority']);
    Route::post('/support/{uuid}/assign', [App\Http\Controllers\Admin\TicketController::class, 'assign']);

    Route::get('/settings', [App\Http\Controllers\Admin\SettingController::class, 'index']);
    Route::match(['get', 'post'], '/settings/general', [App\Http\Controllers\Admin\SettingController::class, 'general']);
    Route::put('/settings', [App\Http\Controllers\Admin\SettingController::class, 'update']);
    Route::get('/settings/smtp', [App\Http\Controllers\Admin\SettingController::class, 'email']);
    Route::get('/settings/broadcast-drivers', [App\Http\Controllers\Admin\SettingController::class, 'broadcast_driver']);
    Route::match(['get', 'post'], '/settings/timezone', [App\Http\Controllers\Admin\SettingController::class, 'timezone']);
    Route::get('/settings/email-templates', [App\Http\Controllers\Admin\EmailTemplateController::class, 'index']);
    Route::get('/settings/email-template/{id}', [App\Http\Controllers\Admin\EmailTemplateController::class, 'show']);
    Route::put('/settings/email-template/{id}', [App\Http\Controllers\Admin\EmailTemplateController::class, 'update']);
    Route::get('/settings/pages', [App\Http\Controllers\Admin\PagesController::class, 'index']);
    Route::post('/settings/pages', [App\Http\Controllers\Admin\PagesController::class, 'store']);
    Route::get('/settings/page/{id}', [App\Http\Controllers\Admin\PagesController::class, 'show']);
    Route::put('/settings/page/{id}', [App\Http\Controllers\Admin\PagesController::class, 'update']);
    Route::delete('/settings/page/{id}', [App\Http\Controllers\Admin\PagesController::class, 'delete']);
    Route::match(['get', 'post'], '/settings/billing', [App\Http\Controllers\Admin\SettingController::class, 'billing']);
    Route::get('/settings/storage', [App\Http\Controllers\Admin\SettingController::class, 'storage']);
    Route::get('/settings/socials', [App\Http\Controllers\Admin\SettingController::class, 'socials']);
    Route::get('/settings/subscription', [App\Http\Controllers\Admin\SettingController::class, 'subscription']);

    Route::get('/user-logs/notifications', [App\Http\Controllers\Admin\NotificationController::class, 'index']);
    Route::get('/user-logs/emails', [App\Http\Controllers\Admin\EmailLogController::class, 'index']);

    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update']);
    Route::put('/profile/password', [App\Http\Controllers\ProfileController::class, 'updatePassword']);
    
    Route::get('/knowledge/categories', [App\Http\Controllers\User\Blog\CategoryController::class, 'index']);
    Route::post('/knowledge/categories/store', [App\Http\Controllers\User\Blog\CategoryController::class, 'store']);
    Route::put('/knowledge/categories/update/{id}', [App\Http\Controllers\User\Blog\CategoryController::class, 'update']);
    Route::delete('/knowledge/categories/delete/{id}', [App\Http\Controllers\User\Blog\CategoryController::class, 'destroy']);
    
    Route::get('/knowledge/sub-categories', [App\Http\Controllers\User\Blog\SubCategoryController::class, 'index']);
    Route::post('/knowledge/sub-categories/store', [App\Http\Controllers\User\Blog\SubCategoryController::class, 'store']);
    Route::put('/knowledge/sub-categories/update/{id}', [App\Http\Controllers\User\Blog\SubCategoryController::class, 'update']);
    Route::delete('/knowledge/sub-categories/delete/{id}', [App\Http\Controllers\User\Blog\SubCategoryController::class, 'destroy']);
    
    Route::get('/knowledge/articles', [App\Http\Controllers\User\Blog\BlogController::class, 'index']);
    Route::post('/knowledge/articles/store', [App\Http\Controllers\User\Blog\BlogController::class, 'store']);
    Route::get('/knowledge/articles/{id}', [App\Http\Controllers\User\Blog\BlogController::class, 'show']);
    Route::put('/knowledge/articles/update/{id}', [App\Http\Controllers\User\Blog\BlogController::class, 'update']);
    Route::delete('/knowledge/articles/delete/{id}', [App\Http\Controllers\User\Blog\BlogController::class, 'destroy']);

    Route::post('/addons/setup/instagram', [Modules\Instagram\Controllers\SetupController::class, 'store']);
    Route::put('/addons/setup/instagram', [Modules\Instagram\Controllers\SetupController::class, 'update']);
    

    // Route::controller(AdminArticleController::class)->group(function () {
    //     Route::get('/articles', 'index');
    //     Route::post('/articles/store', 'store');
    //     Route::get('/articles/{id}', 'show');
    //     Route::put('/articles/update/{id}', 'update');
    //     Route::delete('/articles/delete/{id}', 'destroy');
    // });

    // Route::get('/categories', [App\Http\Controllers\Api\CategoryContoller::class, 'index']);
    // Route::get('/categories/{id}', [App\Http\Controllers\Api\CategoryContoller::class, 'show']);
    // Route::post('/categories/store', [App\Http\Controllers\Api\CategoryContoller::class, 'store']);
    // Route::post('/categories/update/{id}', [App\Http\Controllers\Api\CategoryContoller::class, 'update']);     // _method : PUT
    // Route::delete('/categories/delete/{id}', [App\Http\Controllers\Api\CategoryContoller::class, 'destroy']);
    
    // // Roles
    // Route::get('/roles', [App\Http\Controllers\Api\RoleController::class, 'index']);
    // Route::get('/roles/{id}', [App\Http\Controllers\Api\RoleController::class, 'show']);
    // Route::post('/roles', [App\Http\Controllers\Api\RoleController::class, 'store']);
    // Route::put('/roles/{id}', [App\Http\Controllers\Api\RoleController::class, 'update']);     
    // Route::delete('/roles/{id}', [App\Http\Controllers\Api\RoleController::class, 'destroy']);

    // // Permissions
    // Route::get('/permissions', [App\Http\Controllers\Api\PermissionController::class, 'index']);
    // Route::get('/permissions/{id}', [App\Http\Controllers\Api\PermissionController::class, 'show']);
    // Route::post('/permissions', [App\Http\Controllers\Api\PermissionController::class, 'store']);
    // Route::put('/permissions/{id}', [App\Http\Controllers\Api\PermissionController::class, 'update']);     
    // Route::delete('/permissions/{id}', [App\Http\Controllers\Api\PermissionController::class, 'destroy']);


    // Route::post('/assign-roles-to-users', [App\Http\Controllers\Api\AssignmentController::class, 'assignRolesToUsers']);
});

Route::prefix('api/')->group(function() {
    Route::get('chats/{lastmessagetime}', [App\Http\Controllers\User\ChatController::class, 'chatlist']);
    Route::get('chat/{contact}', [App\Http\Controllers\User\ChatController::class, 'chatmessages']);
    Route::post('send/{contact}', [App\Http\Controllers\User\ChatController::class, 'sendMessageToContact']);
    Route::post('sendimage/{contact}', [App\Http\Controllers\User\ChatController::class, 'sendImageMessageToContact']);
    Route::post('sendfile/{contact}', [App\Http\Controllers\User\ChatController::class, 'sendDocumentMessageToContact']);
});
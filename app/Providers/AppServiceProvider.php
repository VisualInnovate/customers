<?php

namespace App\Providers;

use App\Services\Payment\Gateways\HesabeGateway;
use Hesabe\Payment\Payment;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Observers\ActionLogObserver;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactGroup;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(HesabeGateway::class, function ($app) {
            return new HesabeGateway(
                config('payment.hesabe.merchant_code'),
                config('payment.hesabe.secret_key'),
                config('payment.hesabe.iv_key'),
                config('payment.hesabe.access_code'),
                config('payment.hesabe.environment')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        
        if (!\App::environment('local')) {
            \URL::forceScheme('https');
        }

        $models = [
            \App\Models\User::class,
            \App\Models\UserAdmin::class,
            \App\Models\ContactField::class,
            \App\Models\Contact::class,
            \App\Models\ContactGroup::class,
            \App\Models\Coupon::class,
            \App\Models\EmailLog::class,
            \App\Models\EmailTemplate::class,
            \App\Models\Faq::class,
            \App\Models\Language::class,
            \App\Models\Module::class,
            \App\Models\Organization::class,
            \App\Models\OrganizationApiKey::class,
            \App\Models\Page::class,
            \App\Models\PasswordResetToken::class,
            \App\Models\PaymentGateway::class,
            \App\Models\Review::class,
            \App\Models\Role::class,
            \App\Models\RolePermission::class,
            \App\Models\Setting::class,
            \App\Models\Subscription::class,
            \App\Models\SubscriptionPlan::class,
            \App\Models\TaxRate::class,
            \App\Models\Team::class,
            \App\Models\TeamInvite::class,
            \App\Models\Template::class,
            \App\Models\Ticket::class,
            \App\Models\TicketCategory::class,
            \App\Models\TicketComment::class,
            \App\Models\Contact::class,
            \App\Models\ChatStatusLog::class,
            \App\Models\ChatTicket::class,
            \App\Models\ChatTicketLog::class,
            \App\Models\ChatLog::class,
            \App\Models\ChatMedia::class,
            \App\Models\ChatNote::class,
            \App\Models\CampaignLog::class,
            \App\Models\CampaignLogRetry::class,
            \App\Models\Chat::class,
            \App\Models\BillingTaxRate::class,
            \App\Models\BillingTransaction::class,
            \App\Models\Campaign::class,
            \App\Models\BillingDebit::class,
            \App\Models\BillingInvoice::class,
            \App\Models\BillingPayment::class,
            \App\Models\Addon::class,
            \App\Models\AutoReply::class,
            \App\Models\BillingCredit::class,
        ];
        foreach ($models as $model) {
            $model::observe(ActionLogObserver::class);
        }
    }
}

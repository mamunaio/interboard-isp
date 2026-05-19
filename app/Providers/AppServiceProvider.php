<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Auth\Policies\StaffUserPolicy;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Policies\CustomerPolicy;
use App\Modules\Customer\Models\CustomerAddress;
use App\Modules\Customer\Policies\CustomerAddressPolicy;
use App\Modules\Customer\Models\CustomerContact;
use App\Modules\Customer\Policies\CustomerContactPolicy;
use App\Modules\Customer\Models\CustomerDocument;
use App\Modules\Customer\Policies\CustomerDocumentPolicy;
use App\Modules\Customer\Models\CustomerContract;
use App\Modules\Customer\Policies\CustomerContractPolicy;
use App\Modules\Customer\Models\ServicePackage;
use App\Modules\Customer\Policies\ServicePackagePolicy;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Policies\CustomerServiceSubscriptionPolicy;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Policies\InvoicePolicy;
use App\Modules\Billing\Models\TaxRule;
use App\Modules\Billing\Policies\TaxRulePolicy;
use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Policies\WalletPolicy;
use App\Modules\Billing\Models\PaymentAllocation;
use App\Modules\Billing\Policies\PaymentAllocationPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Core\Settings\SettingsRegistry::class);
        $this->app->singleton(\App\Core\Notification\NotificationRouter::class);
        $this->app->singleton(\App\Core\Webhook\WebhookDispatcher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(StaffUser::class, StaffUserPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(CustomerAddress::class, CustomerAddressPolicy::class);
        Gate::policy(CustomerContact::class, CustomerContactPolicy::class);
        Gate::policy(CustomerDocument::class, CustomerDocumentPolicy::class);
        Gate::policy(CustomerContract::class, CustomerContractPolicy::class);
        Gate::policy(ServicePackage::class, ServicePackagePolicy::class);
        Gate::policy(CustomerServiceSubscription::class, CustomerServiceSubscriptionPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(TaxRule::class, TaxRulePolicy::class);
        Gate::policy(Wallet::class, WalletPolicy::class);
        Gate::policy(PaymentAllocation::class, PaymentAllocationPolicy::class);
    }
}







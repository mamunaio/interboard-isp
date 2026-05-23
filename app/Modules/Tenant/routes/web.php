<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\Tenant\Http\Livewire\Dashboard;
use App\Modules\Customer\Http\Livewire\CustomerIndex;
use App\Modules\Customer\Http\Livewire\CustomerDetail;
use App\Modules\Customer\Http\Livewire\SubscriptionIndex;
use App\Modules\Customer\Http\Livewire\ServicePlanCrud;
use App\Modules\Billing\Http\Livewire\InvoiceIndex;
use App\Modules\Billing\Http\Livewire\PaymentIntake;
use App\Modules\Billing\Http\Livewire\WalletIndex;
use App\Modules\Billing\Http\Livewire\AllocationIndex;
use App\Modules\Billing\Http\Livewire\GatewayAttemptsIndex;
use App\Modules\Billing\Http\Livewire\SubscriptionActions;
use App\Modules\Billing\Http\Livewire\ReversalsIndex;
use App\Modules\Provisioning\Http\Livewire\ProvisioningIndex;
use App\Modules\Network\Http\Livewire\OnlineUsersIndex;
use App\Modules\Network\Http\Livewire\NetworkSessionsIndex;
use App\Modules\Network\Http\Livewire\RadiusConfigIndex;
use App\Modules\Network\Http\Livewire\OltManager;
use App\Modules\Network\Http\Livewire\OnuProvisioner;
use App\Modules\Billing\Http\Livewire\FinancialStatementsIndex;

Route::get('/dashboard', Dashboard::class)->name('tenant.dashboard');
Route::get('/profile', \App\Modules\Tenant\Http\Livewire\ProfileSettings::class)->name('tenant.profile');
Route::get('/audit', \App\Modules\Tenant\Http\Livewire\AuditTimeline::class)->name('tenant.audit');
Route::get('/search', \App\Modules\Tenant\Http\Livewire\GlobalSearch::class)->name('tenant.search');
Route::get('/customers', CustomerIndex::class)->name('tenant.customers.index');
Route::get('/customers/{id}', CustomerDetail::class)->name('tenant.customers.detail');
Route::get('/subscriptions', SubscriptionIndex::class)->name('tenant.subscriptions.index');
Route::get('/subscriptions/actions', SubscriptionActions::class)->name('tenant.subscriptions.actions');
Route::get('/plans', ServicePlanCrud::class)->name('tenant.plans.index');
Route::get('/invoices', InvoiceIndex::class)->name('tenant.invoices.index');
Route::get('/payments/intake', PaymentIntake::class)->name('tenant.payments.intake');
Route::get('/wallet', WalletIndex::class)->name('tenant.wallet.index');
Route::get('/allocations', AllocationIndex::class)->name('tenant.allocations.index');
Route::get('/gateway-attempts', GatewayAttemptsIndex::class)->name('tenant.gateway-attempts.index');
Route::get('/reversals', ReversalsIndex::class)->name('tenant.reversals.index');
Route::get('/provisioning', ProvisioningIndex::class)->name('tenant.provisioning.index');
Route::get('/online-users', OnlineUsersIndex::class)->name('tenant.online-users.index');
Route::get('/network-sessions', NetworkSessionsIndex::class)->name('tenant.network-sessions.index');
Route::get('/radius', RadiusConfigIndex::class)->name('tenant.radius.index');
Route::get('/olts', OltManager::class)->name('tenant.olts.index');
Route::get('/onu-provision', OnuProvisioner::class)->name('tenant.onu.index');
Route::get('/financial-statements', FinancialStatementsIndex::class)->name('tenant.financial-statements.index');

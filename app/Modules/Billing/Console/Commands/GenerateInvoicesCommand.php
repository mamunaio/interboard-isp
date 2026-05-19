<?php

declare(strict_types=1);

namespace App\Modules\Billing\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Billing\Services\BillingGenerationService;
use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'billing:generate {--date= : The run date for the invoice generation engine (YYYY-MM-DD)}';

    /**
     * The console command description.
     */
    protected $description = 'Manually execute the subscription billing scheduler to generate high-precision invoices';

    private BillingGenerationService $billingService;

    public function __construct(BillingGenerationService $billingService)
    {
        parent::__construct();
        $this->billingService = $billingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dateInput = $this->option('date') ?? today()->toDateString();

        try {
            $runDate = Carbon::parse($dateInput)->toDateString();
        } catch (\Exception $e) {
            $this->error("Invalid date format provided. Please use YYYY-MM-DD.");
            return 1;
        }

        $this->info("Starting subscription invoice generation for run date: {$runDate}...");

        $tenantManager = app(TenantManager::class);

        // Fetch active subscriptions due for billing
        $query = CustomerServiceSubscription::withoutGlobalScopes()
            ->where('status', 'active')
            ->whereNotNull('next_billing_date')
            ->whereDate('next_billing_date', '<=', $runDate);

        $totalProcessed = 0;
        $totalSuccess = 0;
        $totalFailed = 0;

        // Chunk by 100 for maximum memory safety
        $query->chunk(100, function ($subscriptions) use ($tenantManager, $runDate, &$totalProcessed, &$totalSuccess, &$totalFailed) {
            foreach ($subscriptions as $subscription) {
                $totalProcessed++;

                // Scoped context switch and explicit cleanup restoration loop
                $previousTenant = $tenantManager->getTenant();
                try {
                    $tenant = Tenant::findOrFail($subscription->tenant_id);
                    $tenantManager->setTenant($tenant);

                    $invoice = $this->billingService->generateInvoiceForSubscription(
                        (int)$subscription->tenant_id,
                        (int)$subscription->id,
                        $runDate
                    );

                    $totalSuccess++;
                    $this->line("Successfully generated invoice {$invoice->invoice_number} for Subscription Code: {$subscription->subscription_code} (Tenant: {$subscription->tenant_id})");

                } catch (\Exception $e) {
                    $totalFailed++;
                    $errorMessage = "Failed to generate invoice for Subscription ID {$subscription->id} (Code: {$subscription->subscription_code}): " . $e->getMessage();
                    
                    $this->warn($errorMessage);
                    Log::error($errorMessage, [
                        'subscription_id' => $subscription->id,
                        'tenant_id' => $subscription->tenant_id,
                        'exception' => $e
                    ]);
                } finally {
                    // Guarantee zero cross-tenant leakages by explicitly restoring the previous tenant context
                    $tenantManager->setTenant($previousTenant);
                }
            }
        });

        $this->info("Invoice generation complete. Total processed: {$totalProcessed}. Successes: {$totalSuccess}. Failures: {$totalFailed}.");

        return 0;
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ScheduledAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExecuteScheduledActions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'isp:execute-schedules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and execute future customer account modifications that are due today.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = now()->toDateString();
        
        // Load pending actions due today or past due
        $actions = ScheduledAction::where('status', 'pending')
            ->where('target_date', '<=', $today)
            ->get();

        if ($actions->isEmpty()) {
            $this->info('No pending scheduled actions found for execution.');
            return Command::SUCCESS;
        }

        $this->info("Found {$actions->count()} pending scheduled action(s) to process.");

        foreach ($actions as $action) {
            $this->comment("Processing Action ID: {$action->id} (Type: {$action->action_type}) for Customer ID: {$action->customer_id}...");

            try {
                switch ($action->action_type) {
                    case 'suspend':
                        $this->simulateSuspension($action->customer_id);
                        break;
                    case 'reactivate':
                        $this->simulateReactivation($action->customer_id);
                        break;
                    case 'change_package':
                        $packageId = $action->payload['package_id'] ?? null;
                        $this->simulatePackageChange($action->customer_id, $packageId);
                        break;
                    default:
                        throw new \Exception("Unsupported action type: {$action->action_type}");
                }

                // Mark as successfully executed
                $action->update([
                    'status' => 'executed',
                    'remarks' => $action->remarks . ' [Executed automatically on ' . now()->toDateTimeString() . ']',
                ]);

                Log::info("Scheduled Action successfully executed.", [
                    'action_id' => $action->id,
                    'action_type' => $action->action_type,
                    'customer_id' => $action->customer_id,
                ]);

                $this->info("Action ID: {$action->id} executed successfully.");

            } catch (Throwable $e) {
                // Mark as failed
                $action->update([
                    'status' => 'failed',
                    'remarks' => $action->remarks . ' [Failed: ' . $e->getMessage() . ']',
                ]);

                Log::error("Scheduled Action execution failed.", [
                    'action_id' => $action->id,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Action ID: {$action->id} failed: " . $e->getMessage());
            }
        }

        $this->info('Scheduled actions processing complete.');
        return Command::SUCCESS;
    }

    /**
     * Simulate customer account suspension.
     */
    protected function simulateSuspension(int $customerId): void
    {
        // Real logic would pull the customer/subscription service and invoke suspension:
        // app(\App\Modules\Customer\Services\CustomerService::class)->suspend($customerId);
        Log::debug("Simulated PPPoE Suspension invoked for customer: {$customerId}");
    }

    /**
     * Simulate customer account reactivation.
     */
    protected function simulateReactivation(int $customerId): void
    {
        Log::debug("Simulated PPPoE Reactivation invoked for customer: {$customerId}");
    }

    /**
     * Simulate speed profile / service package change.
     */
    protected function simulatePackageChange(int $customerId, ?int $packageId): void
    {
        if (empty($packageId)) {
            throw new \Exception("Missing payload package_id for package change action.");
        }
        Log::debug("Simulated Service Plan modification invoked for customer: {$customerId} to package: {$packageId}");
    }
}

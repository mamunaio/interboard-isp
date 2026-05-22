<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\PaymentReceipt;
use Illuminate\Support\Facades\Log;

class ReconciliationService
{
    /**
     * Reconcile an array of gateway transactions against internal PaymentReceipts.
     * $gatewayTransactions = [ ['transaction_id' => 'TXN-123', 'amount' => 500, 'status' => 'completed'] ]
     */
    public function reconcileGatewayBatch(int $tenantId, array $gatewayTransactions): array
    {
        $results = [
            'matched' => 0,
            'unmatched' => 0,
            'discrepancies' => [],
        ];

        foreach ($gatewayTransactions as $tx) {
            $receipt = PaymentReceipt::where('tenant_id', $tenantId)
                ->where('reference', $tx['transaction_id']) // Assuming gateway txn ID is stored in 'reference'
                ->first();

            if (!$receipt) {
                $results['unmatched']++;
                $results['discrepancies'][] = "Transaction {$tx['transaction_id']} exists in gateway but no receipt found in system.";
                continue;
            }

            if ((float)$receipt->amount !== (float)$tx['amount']) {
                $results['unmatched']++;
                $results['discrepancies'][] = "Amount mismatch for {$tx['transaction_id']}: System({$receipt->amount}) vs Gateway({$tx['amount']})";
                continue;
            }

            $results['matched']++;
        }

        Log::info("Reconciliation Batch Completed.", $results);

        return $results;
    }
}

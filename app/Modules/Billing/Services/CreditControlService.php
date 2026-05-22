<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Customer\Models\Customer;
use App\Modules\Billing\Models\Invoice;

class CreditControlService
{
    /**
     * Check if a customer has exceeded their configured credit limit.
     */
    public function hasExceededCreditLimit(Customer $customer): bool
    {
        // If they have no credit limit and allow_overdue_usage is true, they have infinite credit.
        // For ISPs, usually credit_limit is 0, meaning NO credit allowed.
        
        $totalOutstanding = Invoice::where('customer_id', $customer->id)
            ->whereIn('status', ['issued', 'overdue'])
            ->sum('amount_due');

        // If outstanding is strictly greater than their credit limit, they exceeded it.
        return $totalOutstanding > $customer->credit_limit;
    }

    /**
     * Check if generating a new invoice of a specific amount will breach the limit.
     */
    public function canGenerateInvoice(Customer $customer, float $newInvoiceAmount): bool
    {
        $totalOutstanding = Invoice::where('customer_id', $customer->id)
            ->whereIn('status', ['issued', 'overdue'])
            ->sum('amount_due');

        $projectedOutstanding = $totalOutstanding + $newInvoiceAmount;

        return $projectedOutstanding <= $customer->credit_limit;
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\Invoice;
use Illuminate\Support\Facades\DB;

class TaxRemittanceService
{
    /**
     * Calculate the total tax liability collected from paid invoices for a given period.
     */
    public function calculateTaxLiability(int $tenantId, string $periodStart, string $periodEnd): float
    {
        // Get all fully paid invoices in the period
        $taxCollected = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereBetween('issue_date', [$periodStart, $periodEnd])
            ->sum('tax_total');

        return (float) $taxCollected;
    }

    /**
     * Record a tax remittance payment to the government/tax authority.
     */
    public function recordRemittance(int $tenantId, string $periodStart, string $periodEnd, float $amountRemitted, string $reference): void
    {
        $liability = $this->calculateTaxLiability($tenantId, $periodStart, $periodEnd);

        DB::table('tax_remittances')->insert([
            'tenant_id' => $tenantId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_tax_collected' => $liability,
            'total_tax_remitted' => $amountRemitted,
            'status' => ($amountRemitted >= $liability) ? 'paid' : 'pending',
            'payment_reference' => $reference,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

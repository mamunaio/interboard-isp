<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

class ProrationCalculatorService
{
    /**
     * Calculate the prorated charge when a customer upgrades/downgrades a plan mid-cycle.
     * 
     * @param float $oldMonthlyPrice The price of the previous plan.
     * @param float $newMonthlyPrice The price of the new plan.
     * @param int $totalDaysInCycle Total days in the current billing cycle (usually 30).
     * @param int $daysUsed The number of days the old plan was used before the change.
     * @return float The net amount to charge (or credit if negative) the customer.
     */
    public function calculateProration(float $oldMonthlyPrice, float $newMonthlyPrice, int $totalDaysInCycle, int $daysUsed): float
    {
        if ($totalDaysInCycle <= 0 || $daysUsed < 0 || $daysUsed > $totalDaysInCycle) {
            throw new \InvalidArgumentException("Invalid days configuration for proration.");
        }

        $daysRemaining = $totalDaysInCycle - $daysUsed;

        $oldDailyRate = $oldMonthlyPrice / $totalDaysInCycle;
        $newDailyRate = $newMonthlyPrice / $totalDaysInCycle;

        // Credit for unused days of the old plan
        $unusedCredit = $daysRemaining * $oldDailyRate;

        // Charge for the remaining days on the new plan
        $newCharge = $daysRemaining * $newDailyRate;

        // Net difference (Positive means they owe money, Negative means they get a credit)
        return round($newCharge - $unusedCredit, 4);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\TaxRule;
use App\Modules\Billing\Enums\TaxCalculationMode;
use App\Modules\Billing\Enums\TaxType;
use Illuminate\Support\Collection;

class TaxCalculationService
{
    /**
     * Stateless pure method to calculate tax details, totals, and base decomposition.
     * 
     * @param string $subtotal The input subtotal (acts as total for inclusive, base for exclusive)
     * @param string $invoiceType The type of invoice (subscription, installation, etc.)
     * @param array|Collection $taxRules The tax rules to apply
     */
    public function calculateTaxes(string $subtotal, string $invoiceType, $taxRules): array
    {
        // 1. Convert taxRules to a Collection for cleaner operations
        $rules = $taxRules instanceof Collection ? $taxRules : collect($taxRules);

        // Normalize subtotal string
        $subtotal = bcadd($subtotal, '0', 4);

        // 2. Filter active rules that apply to this invoice type
        $applicableRules = $rules->filter(function (TaxRule $rule) use ($invoiceType) {
            return $rule->active && is_array($rule->applies_to_invoice_types) && in_array($invoiceType, $rule->applies_to_invoice_types, true);
        });

        // 3. Partition rules by calculation mode
        $inclusiveRules = $applicableRules->filter(fn(TaxRule $r) => $r->calculation_mode === TaxCalculationMode::INCLUSIVE)->values();
        $exclusiveRules = $applicableRules->filter(fn(TaxRule $r) => $r->calculation_mode === TaxCalculationMode::EXCLUSIVE)->values();

        $taxDetails = [];
        $taxTotal = '0.0000';

        // --- PART A: INCLUSIVE TAX DECOMPOSITION ---
        if ($inclusiveRules->isNotEmpty()) {
            // Sort inclusive rules in descending order of application:
            // 1. compound rules first, in descending order of compound_order
            // 2. non-compound rules last, in descending order of ID
            $inclusiveRulesSorted = $inclusiveRules->sort(function (TaxRule $a, TaxRule $b) {
                if ($a->is_compound !== $b->is_compound) {
                    return $b->is_compound <=> $a->is_compound;
                }
                if ($a->is_compound) {
                    return ($b->compound_order ?? 0) <=> ($a->compound_order ?? 0);
                }
                return $b->id <=> $a->id;
            })->values();

            $runningTotal = $subtotal;
            $inclusiveAmounts = [];

            foreach ($inclusiveRulesSorted as $rule) {
                if ($rule->tax_type === TaxType::PERCENTAGE) {
                    $divisor = bcadd('1.0000', bcdiv($rule->rate, '100.0000', 4), 4);
                    $newTotal = bcdiv($runningTotal, $divisor, 4);
                    $amt = bcsub($runningTotal, $newTotal, 4);
                    $runningTotal = $newTotal;
                } else {
                    $newTotal = bcsub($runningTotal, $rule->fixed_amount, 4);
                    if (bccomp($newTotal, '0.0000', 4) === -1) {
                        throw new \InvalidArgumentException("The subtotal is less than the inclusive fixed tax.");
                    }
                    $amt = bcadd($rule->fixed_amount, '0', 4);
                    $runningTotal = $newTotal;
                }
                $inclusiveAmounts[$rule->id] = $amt;
            }

            // Decomposed base is what remains after extracting all inclusive rules
            $base = $runningTotal;

            // Add inclusive taxes to details (matching their original sequence or ID order)
            foreach ($inclusiveRules as $rule) {
                $amt = $inclusiveAmounts[$rule->id];
                $taxDetails[] = [
                    'tax_rule_id' => $rule->id,
                    'code' => $rule->code,
                    'name' => $rule->name,
                    'calculation_mode' => 'inclusive',
                    'amount' => $amt,
                ];
                $taxTotal = bcadd($taxTotal, $amt, 4);
            }
        } else {
            $base = $subtotal;
        }

        // --- PART B: EXCLUSIVE & COMPOUND TAXES ---
        // Partition exclusive rules into non-compound and compound
        $nonCompoundExclusive = $exclusiveRules->filter(fn(TaxRule $r) => !$r->is_compound);
        $compoundExclusive = $exclusiveRules->filter(fn(TaxRule $r) => $r->is_compound)
            ->sortBy('compound_order')
            ->values();

        // 1. Non-compound exclusive rules calculated against the base
        foreach ($nonCompoundExclusive as $rule) {
            if ($rule->tax_type === TaxType::PERCENTAGE) {
                $pct = bcdiv($rule->rate, '100.0000', 4);
                $amt = bcmul($base, $pct, 4);
            } else {
                $amt = bcadd($rule->fixed_amount, '0', 4);
            }

            $taxDetails[] = [
                'tax_rule_id' => $rule->id,
                'code' => $rule->code,
                'name' => $rule->name,
                'calculation_mode' => 'exclusive',
                'amount' => $amt,
            ];
            $taxTotal = bcadd($taxTotal, $amt, 4);
        }

        // 2. Compound exclusive rules calculated against original base + all previously computed taxes (inclusive, non-compound exclusive, and prior compound orders)
        // Wait, inclusive + base = original subtotal. Let's start the compound running base at original subtotal and add any prior exclusive non-compound taxes.
        $compoundRunningBase = $subtotal;
        foreach ($taxDetails as $detail) {
            if ($detail['calculation_mode'] === 'exclusive') {
                $compoundRunningBase = bcadd($compoundRunningBase, $detail['amount'], 4);
            }
        }

        foreach ($compoundExclusive as $rule) {
            if ($rule->tax_type === TaxType::PERCENTAGE) {
                $pct = bcdiv($rule->rate, '100.0000', 4);
                $amt = bcmul($compoundRunningBase, $pct, 4);
            } else {
                $amt = bcadd($rule->fixed_amount, '0', 4);
            }

            $taxDetails[] = [
                'tax_rule_id' => $rule->id,
                'code' => $rule->code,
                'name' => $rule->name,
                'calculation_mode' => 'exclusive',
                'amount' => $amt,
            ];
            $taxTotal = bcadd($taxTotal, $amt, 4);

            // Layer this tax onto the running base for the next compound order rule
            $compoundRunningBase = bcadd($compoundRunningBase, $amt, 4);
        }

        // Grand Total is decomposed base + total calculated taxes
        $grandTotal = bcadd($base, $taxTotal, 4);

        return [
            'base_decomposition' => $base,
            'tax_details' => $taxDetails,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
        ];
    }
}

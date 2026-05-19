<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\TaxRule;
use App\Modules\Billing\Enums\TaxType;
use App\Modules\Billing\Enums\TaxCalculationMode;
use App\Modules\Billing\Enums\InvoiceType;
use Illuminate\Validation\ValidationException;

class TaxRuleService
{
    /**
     * Create a new tax rule with strict validation.
     */
    public function createTaxRule(int $tenantId, array $data): TaxRule
    {
        // 1. Enforce unique code constraint per tenant
        $code = trim((string)($data['code'] ?? ''));
        if ($code === '') {
            throw ValidationException::withMessages([
                'code' => ['Tax rule code is required.']
            ]);
        }

        $duplicate = TaxRule::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'code' => ['Tax rule code must be unique for this tenant.']
            ]);
        }

        // 2. Validate enums
        $taxType = isset($data['tax_type'])
            ? ($data['tax_type'] instanceof TaxType ? $data['tax_type'] : TaxType::from($data['tax_type']))
            : TaxType::PERCENTAGE;

        $calcMode = isset($data['calculation_mode'])
            ? ($data['calculation_mode'] instanceof TaxCalculationMode ? $data['calculation_mode'] : TaxCalculationMode::from($data['calculation_mode']))
            : TaxCalculationMode::EXCLUSIVE;

        // 3. Validate amounts based on TaxType
        $rate = '0.0000';
        $fixedAmount = '0.0000';

        if ($taxType === TaxType::PERCENTAGE) {
            $rawRate = (string)($data['rate'] ?? '0.0000');
            if (bccomp($rawRate, '0.0000', 4) <= 0 || bccomp($rawRate, '100.0000', 4) > 0) {
                throw ValidationException::withMessages([
                    'rate' => ['Tax percentage rate must be greater than 0 and less than or equal to 100.']
                ]);
            }
            $rate = bcadd($rawRate, '0', 4);
        } else {
            $rawFixed = (string)($data['fixed_amount'] ?? '0.0000');
            if (bccomp($rawFixed, '0.0000', 4) < 0) {
                throw ValidationException::withMessages([
                    'fixed_amount' => ['Tax fixed amount must be greater than or equal to 0.']
                ]);
            }
            $fixedAmount = bcadd($rawFixed, '0', 4);
        }

        // 4. Validate compound rules strictly
        $isCompound = (bool)($data['is_compound'] ?? false);
        $compoundOrder = null;

        if ($isCompound) {
            if (!isset($data['compound_order']) || $data['compound_order'] === null) {
                throw ValidationException::withMessages([
                    'compound_order' => ['Compound order is required when is_compound is true.']
                ]);
            }
            $orderVal = $data['compound_order'];
            if (!is_int($orderVal) && !ctype_digit((string)$orderVal)) {
                throw ValidationException::withMessages([
                    'compound_order' => ['Compound order must be an integer.']
                ]);
            }
            $compoundOrder = (int)$orderVal;
            if ($compoundOrder < 1) {
                throw ValidationException::withMessages([
                    'compound_order' => ['Compound order must be >= 1.']
                ]);
            }
        } else {
            if (isset($data['compound_order']) && $data['compound_order'] !== null) {
                throw ValidationException::withMessages([
                    'compound_order' => ['Compound order must be null when is_compound is false.']
                ]);
            }
        }

        // 5. Validate applies_to_invoice_types array strictly
        $appliesTo = [];
        if (isset($data['applies_to_invoice_types'])) {
            if (!is_array($data['applies_to_invoice_types'])) {
                throw ValidationException::withMessages([
                    'applies_to_invoice_types' => ['Applies to invoice types must be an array.']
                ]);
            }
            $validTypes = array_map(fn($case) => $case->value, InvoiceType::cases());
            foreach ($data['applies_to_invoice_types'] as $type) {
                $typeStr = (string)$type;
                if (!in_array($typeStr, $validTypes, true)) {
                    throw ValidationException::withMessages([
                        'applies_to_invoice_types' => ["Invalid invoice type '{$typeStr}' specified."]
                    ]);
                }
                if (in_array($typeStr, $appliesTo, true)) {
                    throw ValidationException::withMessages([
                        'applies_to_invoice_types' => ["Duplicate invoice type '{$typeStr}' is not allowed."]
                    ]);
                }
                $appliesTo[] = $typeStr;
            }
        }

        // 6. Create Tax Rule record
        return TaxRule::create([
            'tenant_id' => $tenantId,
            'name' => trim((string)($data['name'] ?? 'Tax Rule')),
            'code' => $code,
            'tax_type' => $taxType,
            'calculation_mode' => $calcMode,
            'is_compound' => $isCompound,
            'compound_order' => $compoundOrder,
            'rate' => $rate,
            'fixed_amount' => $fixedAmount,
            'applies_to_invoice_types' => $appliesTo,
            'active' => (bool)($data['active'] ?? true),
        ]);
    }

    /**
     * Update an existing tax rule securely.
     */
    public function updateTaxRule(TaxRule $taxRule, array $data): TaxRule
    {
        $tenantId = (int)$taxRule->tenant_id;

        // 1. Enforce unique code constraint if updated
        if (isset($data['code'])) {
            $code = trim((string)$data['code']);
            if ($code === '') {
                throw ValidationException::withMessages([
                    'code' => ['Tax rule code is required.']
                ]);
            }

            if ($code !== $taxRule->code) {
                $duplicate = TaxRule::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('id', '!=', $taxRule->id)
                    ->where('code', $code)
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'code' => ['Tax rule code must be unique for this tenant.']
                    ]);
                }
                $taxRule->code = $code;
            }
        }

        // 2. Validate and cast enums if provided
        $taxType = isset($data['tax_type'])
            ? ($data['tax_type'] instanceof TaxType ? $data['tax_type'] : TaxType::from($data['tax_type']))
            : $taxRule->tax_type;

        $calcMode = isset($data['calculation_mode'])
            ? ($data['calculation_mode'] instanceof TaxCalculationMode ? $data['calculation_mode'] : TaxCalculationMode::from($data['calculation_mode']))
            : $taxRule->calculation_mode;

        // Update basic attributes
        if (isset($data['name'])) {
            $taxRule->name = trim((string)$data['name']);
        }
        $taxRule->tax_type = $taxType;
        $taxRule->calculation_mode = $calcMode;

        // 3. Validate amounts based on current / updated TaxType
        if ($taxType === TaxType::PERCENTAGE) {
            $rawRate = (string)($data['rate'] ?? $taxRule->rate);
            if (bccomp($rawRate, '0.0000', 4) <= 0 || bccomp($rawRate, '100.0000', 4) > 0) {
                throw ValidationException::withMessages([
                    'rate' => ['Tax percentage rate must be greater than 0 and less than or equal to 100.']
                ]);
            }
            $taxRule->rate = bcadd($rawRate, '0', 4);
            $taxRule->fixed_amount = '0.0000';
        } else {
            $rawFixed = (string)($data['fixed_amount'] ?? $taxRule->fixed_amount);
            if (bccomp($rawFixed, '0.0000', 4) < 0) {
                throw ValidationException::withMessages([
                    'fixed_amount' => ['Tax fixed amount must be greater than or equal to 0.']
                ]);
            }
            $taxRule->fixed_amount = bcadd($rawFixed, '0', 4);
            $taxRule->rate = '0.0000';
        }

        // 4. Validate compound rules strictly
        $isCompound = isset($data['is_compound']) ? (bool)$data['is_compound'] : $taxRule->is_compound;
        $taxRule->is_compound = $isCompound;

        if ($isCompound) {
            $orderVal = $data['compound_order'] ?? $taxRule->compound_order;
            if ($orderVal === null) {
                throw ValidationException::withMessages([
                    'compound_order' => ['Compound order is required when is_compound is true.']
                ]);
            }
            if (!is_int($orderVal) && !ctype_digit((string)$orderVal)) {
                throw ValidationException::withMessages([
                    'compound_order' => ['Compound order must be an integer.']
                ]);
            }
            $compoundOrder = (int)$orderVal;
            if ($compoundOrder < 1) {
                throw ValidationException::withMessages([
                    'compound_order' => ['Compound order must be >= 1.']
                ]);
            }
            $taxRule->compound_order = $compoundOrder;
        } else {
            // If is_compound is false, compound_order must be null
            $orderVal = array_key_exists('compound_order', $data) ? $data['compound_order'] : $taxRule->compound_order;
            if ($orderVal !== null) {
                throw ValidationException::withMessages([
                    'compound_order' => ['Compound order must be null when is_compound is false.']
                ]);
            }
            $taxRule->compound_order = null;
        }

        // 5. Validate applies_to_invoice_types array strictly if provided
        if (isset($data['applies_to_invoice_types'])) {
            if (!is_array($data['applies_to_invoice_types'])) {
                throw ValidationException::withMessages([
                    'applies_to_invoice_types' => ['Applies to invoice types must be an array.']
                ]);
            }
            $appliesTo = [];
            $validTypes = array_map(fn($case) => $case->value, InvoiceType::cases());
            foreach ($data['applies_to_invoice_types'] as $type) {
                $typeStr = (string)$type;
                if (!in_array($typeStr, $validTypes, true)) {
                    throw ValidationException::withMessages([
                        'applies_to_invoice_types' => ["Invalid invoice type '{$typeStr}' specified."]
                    ]);
                }
                if (in_array($typeStr, $appliesTo, true)) {
                    throw ValidationException::withMessages([
                        'applies_to_invoice_types' => ["Duplicate invoice type '{$typeStr}' is not allowed."]
                    ]);
                }
                $appliesTo[] = $typeStr;
            }
            $taxRule->applies_to_invoice_types = $appliesTo;
        }

        if (isset($data['active'])) {
            $taxRule->active = (bool)$data['active'];
        }

        $taxRule->save();

        return $taxRule;
    }

    /**
     * Delete an existing tax rule.
     */
    public function deleteTaxRule(TaxRule $taxRule): void
    {
        $taxRule->delete();
    }
}

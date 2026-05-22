<?php

declare(strict_types=1);

namespace App\Modules\CRM\Services;

use App\Modules\CRM\Enums\InteractionType;
use App\Modules\CRM\Enums\LeadStatus;
use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Models\LeadInteraction;
use App\Modules\Customer\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadService
{
    /**
     * Create a new Lead.
     */
    public function createLead(array $data): Lead
    {
        return Lead::create($data);
    }

    /**
     * Log a new interaction for a lead.
     */
    public function addInteraction(
        Lead $lead,
        InteractionType $type,
        string $summary,
        ?string $nextFollowUpDate = null,
        ?int $userId = null
    ): LeadInteraction {
        return DB::transaction(function () use ($lead, $type, $summary, $nextFollowUpDate, $userId) {
            $interaction = LeadInteraction::create([
                'lead_id' => $lead->id,
                'user_id' => $userId,
                'type' => $type,
                'summary' => $summary,
                'next_follow_up_date' => $nextFollowUpDate,
            ]);

            // Auto-update status based on interaction if it's still new
            if ($lead->status === LeadStatus::NEW && $type !== InteractionType::NOTE) {
                $lead->update(['status' => LeadStatus::CONTACTED]);
            }

            return $interaction;
        });
    }

    /**
     * Convert a Lead into an actual Customer.
     */
    public function convertToCustomer(Lead $lead): Customer
    {
        if ($lead->is_converted) {
            throw new \Exception("This lead has already been converted.");
        }

        return DB::transaction(function () use ($lead) {
            // Generate a customer code
            $customerCode = 'CUST-' . strtoupper(Str::random(6));

            $customer = Customer::create([
                'tenant_id' => $lead->tenant_id,
                'customer_code' => $customerCode,
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'address' => $lead->address,
                'status' => 'active', // Assuming you have an active status
                'service_plan_id' => $lead->expected_plan_id,
                'billing_type' => 'prepaid', // Default assumption
            ]);

            $lead->update([
                'status' => LeadStatus::WON,
                'is_converted' => true,
            ]);

            // Optionally, log this conversion as an interaction
            LeadInteraction::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'type' => InteractionType::NOTE,
                'summary' => 'Lead converted to customer: ' . $customerCode,
            ]);

            return $customer;
        });
    }
}

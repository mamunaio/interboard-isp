<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

class SuspensionEvaluationResult
{
    /**
     * SuspensionEvaluationResult constructor.
     *
     * @param bool $eligible
     * @param string[] $reasons
     * @param int[] $triggeringInvoiceIds
     */
    public function __construct(
        public bool $eligible,
        public array $reasons,
        public array $triggeringInvoiceIds = []
    ) {}
}

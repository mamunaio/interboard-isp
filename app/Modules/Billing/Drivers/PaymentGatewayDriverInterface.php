<?php

declare(strict_types=1);

namespace App\Modules\Billing\Drivers;

use App\Modules\Billing\Models\GatewayPaymentAttempt;

interface PaymentGatewayDriverInterface
{
    /**
     * Build the request to initiate checkout redirect or parameters.
     */
    public function initiate(GatewayPaymentAttempt $attempt, array $options = []): array;

    /**
     * Cryptographically validate callback signature.
     */
    public function validateSignature(array $payload, array $headers = []): bool;

    /**
     * Query gateway's official back-channel endpoint to verify payment status and amount.
     */
    public function verify(string $gatewayTransactionId, string $expectedAmount): array;
}

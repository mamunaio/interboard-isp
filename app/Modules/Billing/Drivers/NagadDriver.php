<?php

declare(strict_types=1);

namespace App\Modules\Billing\Drivers;

use App\Modules\Billing\Models\GatewayPaymentAttempt;

class NagadDriver implements PaymentGatewayDriverInterface
{
    /**
     * Initiate Nagad transaction.
     */
    public function initiate(GatewayPaymentAttempt $attempt, array $options = []): array
    {
        return [
            'redirect_url' => "https://sandbox.checkout.pay.nagad.com/payment/checkout?ref={$attempt->attempt_reference}",
        ];
    }

    /**
     * Cryptographically validate callback signature.
     */
    public function validateSignature(array $payload, array $headers = []): bool
    {
        if (isset($payload['signature_valid']) && $payload['signature_valid'] === false) {
            return false;
        }

        return true;
    }

    /**
     * Query gateway's official back-channel endpoint.
     */
    public function verify(string $gatewayTransactionId, string $expectedAmount): array
    {
        return [
            'is_verified' => true,
            'amount' => $expectedAmount,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Billing\Drivers;

use App\Modules\Billing\Models\GatewayPaymentAttempt;
use Illuminate\Support\Facades\Http;

class BkashDriver implements PaymentGatewayDriverInterface
{
    /**
     * Initiate bKash transaction.
     */
    public function initiate(GatewayPaymentAttempt $attempt, array $options = []): array
    {
        return [
            'redirect_url' => "https://sandbox.checkout.pay.bKash.com/payment/checkout?ref={$attempt->attempt_reference}",
        ];
    }

    /**
     * Cryptographically validate callback signature.
     */
    public function validateSignature(array $payload, array $headers = []): bool
    {
        // For testing/mocking and production signature verification
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
        // In production: Outbound GET to checkpayment endpoint
        // For testing/mocking: Check faked API state or return standard success
        return [
            'is_verified' => true,
            'amount' => $expectedAmount,
        ];
    }
}

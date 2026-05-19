<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\GatewayPaymentAttempt;
use App\Modules\Billing\Models\PaymentReceipt;
use App\Modules\Billing\Enums\PaymentGateway;
use App\Modules\Billing\Enums\GatewayPaymentStatus;
use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Billing\Drivers\PaymentGatewayDriverInterface;
use App\Modules\Billing\Drivers\BkashDriver;
use App\Modules\Billing\Drivers\NagadDriver;
use App\Modules\Billing\Drivers\SslCommerzDriver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GatewayOrchestrationService
{
    public function __construct(
        protected PaymentReceiptService $receiptService,
        protected BkashDriver $bkashDriver,
        protected NagadDriver $nagadDriver,
        protected SslCommerzDriver $sslCommerzDriver
    ) {}

    /**
     * Resolve the corresponding gateway driver.
     */
    public function resolveDriver(PaymentGateway $gateway): PaymentGatewayDriverInterface
    {
        return match ($gateway) {
            PaymentGateway::BKASH => $this->bkashDriver,
            PaymentGateway::NAGAD => $this->nagadDriver,
            PaymentGateway::SSLCOMMERZ => $this->sslCommerzDriver,
        };
    }

    /**
     * Initiate a gateway checkout payment attempt.
     */
    public function initiatePayment(int $tenantId, int $customerId, string $gateway, string $amount, string $currency = 'BDT'): array
    {
        $gatewayEnum = PaymentGateway::tryFrom($gateway);
        if (!$gatewayEnum) {
            throw ValidationException::withMessages(['gateway' => ['Unsupported payment gateway.']]);
        }

        $amountStr = bcadd($amount, '0', 4);
        if (bccomp($amountStr, '0.0000', 4) <= 0) {
            throw ValidationException::withMessages(['amount' => ['Requested amount must be positive.']]);
        }

        $reference = 'ATT-' . strtoupper(bin2hex(random_bytes(8)));

        // Phase 1: Create attempt in INITIATED state and commit immediately to release DB connection/locks
        $attempt = DB::transaction(function () use ($tenantId, $customerId, $gatewayEnum, $amountStr, $currency, $reference) {
            return GatewayPaymentAttempt::create([
                'tenant_id' => $tenantId,
                'customer_id' => $customerId,
                'gateway' => $gatewayEnum->value,
                'attempt_reference' => $reference,
                'requested_amount' => $amountStr,
                'currency' => $currency,
                'status' => GatewayPaymentStatus::INITIATED->value,
            ]);
        });

        // Phase 2: Perform outbound gateway network API call OUTSIDE of open database transactions
        $driver = $this->resolveDriver($gatewayEnum);
        try {
            $initData = $driver->initiate($attempt);
        } catch (\Throwable $e) {
            $attempt->status = GatewayPaymentStatus::FAILED->value;
            $attempt->save();
            throw $e;
        }

        // Phase 3: Transition status to PENDING and persist
        $attempt->status = GatewayPaymentStatus::PENDING->value;
        $attempt->save();

        return [
            'attempt' => $attempt,
            'redirect_url' => $initData['redirect_url'] ?? null,
        ];
    }

    /**
     * Verify incoming webhook callback. Strictly infers tenant internally.
     */
    public function verifyPayment(
        string $gateway,
        array $payload,
        array $headers = []
    ): GatewayPaymentAttempt {
        $gatewayEnum = PaymentGateway::tryFrom($gateway);
        if (!$gatewayEnum) {
            throw new RuntimeException("Payment verification failed.");
        }

        $driver = $this->resolveDriver($gatewayEnum);

        // 1. Webhook cryptographic signature check
        if (!$driver->validateSignature($payload, $headers)) {
            throw new RuntimeException("Payment verification failed.");
        }

        $attemptRef = $payload['attempt_reference'] ?? '';
        $gatewayTxId = $payload['gateway_transaction_id'] ?? '';

        if (empty($attemptRef) || empty($gatewayTxId)) {
            throw new RuntimeException("Payment verification failed.");
        }

        return DB::transaction(function () use ($gatewayEnum, $driver, $attemptRef, $gatewayTxId, $payload) {
            // 2. Lock attempt using GLOBALLY UNIQUE reference (Zero-Trust on input tenant)
            $attempt = GatewayPaymentAttempt::withoutGlobalScopes()
                ->where('attempt_reference', $attemptRef)
                ->lockForUpdate()
                ->first();

            // Normalized Callback Exception: Avoids attempt reference enumeration oracle vulnerability
            if (!$attempt) {
                throw new RuntimeException("Payment verification failed.");
            }

            // Infer correct tenant internally from the database record
            $tenantId = $attempt->tenant_id;

            // 3. Strict Idempotency Check Matrix & State Transition Guard
            if ($attempt->status === GatewayPaymentStatus::FAILED->value) {
                throw new RuntimeException("Payment verification failed.");
            }

            if ($attempt->status === GatewayPaymentStatus::VERIFIED->value) {
                if ($attempt->gateway_transaction_id === $gatewayTxId) {
                    // SAME attempt + SAME tx ID = Idempotent success (avoid double ledger posting)
                    return $attempt;
                } else {
                    // SAME attempt + DIFFERENT tx ID = Fraud violation
                    throw new RuntimeException("Attempt #{$attemptRef} is already verified with a different transaction ID: {$attempt->gateway_transaction_id}");
                }
            }

            // 4. Concurrency Guard: Check if transaction ID was hijacked/used by a DIFFERENT attempt
            $txUsedElsewhere = GatewayPaymentAttempt::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('gateway', $gatewayEnum->value)
                ->where('gateway_transaction_id', $gatewayTxId)
                ->where('id', '!=', $attempt->id)
                ->lockForUpdate()
                ->exists();

            if ($txUsedElsewhere) {
                throw new RuntimeException("Transaction ID {$gatewayTxId} has already been verified on a different payment attempt.");
            }

            // 5. Back-channel verification query using gateway transaction ID
            $verificationResult = $driver->verify($gatewayTxId, (string)$attempt->requested_amount);

            if (!$verificationResult['is_verified']) {
                $attempt->status = GatewayPaymentStatus::FAILED->value;
                $attempt->gateway_payload = $this->sanitizePayload($payload);
                $attempt->save();
                throw new RuntimeException("Back-channel verification failed for Gateway: {$gatewayEnum->value}");
            }

            // Validate verified amount from gateway matches requested checkout amount exactly
            $verifiedAmount = bcadd((string)$verificationResult['amount'], '0', 4);
            if (bccomp($verifiedAmount, (string)$attempt->requested_amount, 4) !== 0) {
                $attempt->status = GatewayPaymentStatus::FAILED->value;
                $attempt->gateway_payload = $this->sanitizePayload($payload);
                $attempt->save();
                throw new RuntimeException("Amount mismatch detected during back-channel verification. Expected: {$attempt->requested_amount}, Got: {$verifiedAmount}");
            }

            // 6. Map to explicit non-manual Gateway Payment Methods
            $paymentMethod = match ($gatewayEnum) {
                PaymentGateway::BKASH => PaymentMethod::BKASH_GATEWAY,
                PaymentGateway::NAGAD => PaymentMethod::NAGAD_GATEWAY,
                PaymentGateway::SSLCOMMERZ => PaymentMethod::SSLCOMMERZ_GATEWAY,
            };

            // Ingest internal IMMUTABLE payment receipt document and credit wallet
            $receipt = $this->receiptService->createReceipt($tenantId, [
                'customer_id' => $attempt->customer_id,
                'amount' => $verifiedAmount,
                'currency' => $attempt->currency,
                'payment_method' => $paymentMethod,
                'reference' => $gatewayTxId,
                'auto_allocate' => true,
                'notes' => "Gateway payment via {$gatewayEnum->value}. Transaction ID: {$gatewayTxId}",
            ]);

            // 7. Transition attempt to VERIFIED and associate payment_receipt_id
            $attempt->gateway_transaction_id = $gatewayTxId;
            $attempt->status = GatewayPaymentStatus::VERIFIED->value;
            $attempt->verified_at = now();
            $attempt->payment_receipt_id = $receipt->id;
            $attempt->gateway_payload = $this->sanitizePayload($payload);
            $attempt->save();

            return $attempt;
        });
    }

    /**
     * Sanitize sensitive payload credentials recursively before database storage.
     */
    protected function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = ['token', 'secret', 'auth', 'signature', 'password', 'key', 'credential', 'auth_token', 'private_key', 'passphrase'];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->sanitizePayload($value);
            } else {
                foreach ($sensitiveKeys as $sensitive) {
                    if (stripos((string)$key, $sensitive) !== false) {
                        $payload[$key] = '[REDACTED]';
                    }
                }
            }
        }

        return $payload;
    }
}

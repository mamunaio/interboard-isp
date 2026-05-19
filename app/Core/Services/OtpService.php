<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Modules\Customer\Models\OtpVerification;
use Carbon\Carbon;

class OtpService
{
    /**
     * Generate a cryptographically secure 6-digit OTP code and save its hash.
     */
    public function generateOtp(string $phone, string $purpose = 'login', int $expiryMinutes = 5): string
    {
        // 1. Generate secure 6-digit code
        $plaintext = (string)random_int(100000, 999999);

        // 2. Hash code for secure storage
        $codeHash = hash('sha256', $plaintext);

        // 3. Save to database
        OtpVerification::create([
            'phone' => $phone,
            'code_hash' => $codeHash,
            'purpose' => $purpose,
            'expires_at' => Carbon::now()->addMinutes($expiryMinutes),
        ]);

        return $plaintext;
    }

    /**
     * Validate an incoming OTP code against active database records.
     */
    public function validateOtp(string $phone, string $code, string $purpose = 'login'): bool
    {
        $codeHash = hash('sha256', $code);

        // Find the latest unexpired, unverified record for this phone and purpose
        $verification = OtpVerification::where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('code_hash', $codeHash)
            ->where('expires_at', '>', Carbon::now())
            ->whereNull('verified_at')
            ->orderBy('id', 'desc')
            ->first();

        if (!$verification) {
            return false;
        }

        // Mark as verified
        $verification->update([
            'verified_at' => Carbon::now(),
        ]);

        return true;
    }
}

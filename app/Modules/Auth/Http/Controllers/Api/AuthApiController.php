<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Core\Tenant\TenantManager;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Auth\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthApiController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Handle Tenant Staff authentication and issue Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        // 1. Enforce active tenant context
        if (!$this->tenantManager->hasTenant()) {
            return response()->json([
                'success' => false,
                'message' => 'Missing active tenant context. Please access via your dedicated ISP subdomain.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $tenantId = $this->tenantManager->getTenantId();

        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = 'login-staff:' . Str::lower($validated['email']) . '|' . $request->ip();

        // 2. Enforce login throttling
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'success' => false,
                'message' => "Too many login attempts. Please try again in {$seconds} seconds."
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // 3. Authenticate staff user strictly scoped to the active tenant
        $staffUser = StaffUser::where('tenant_id', $tenantId)
            ->where('email', $validated['email'])
            ->first();

        if (!$staffUser || !Hash::check($validated['password'], $staffUser->password)) {
            RateLimiter::hit($throttleKey, 300); // Lock for 5 minutes after 5 failures
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($staffUser->status === 'inactive') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is deactivated. Please contact your administrator.'
            ], Response::HTTP_FORBIDDEN);
        }

        RateLimiter::clear($throttleKey);

        // 4. Update login metadata
        $staffUser->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // 5. Hook: Trusted Device registration
        $this->registerTrustedDevice($staffUser, $request);

        // 6. Issue token
        $token = $staffUser->createToken('staff-api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Authentication successful.',
            'data' => [
                'user' => [
                    'id' => $staffUser->id,
                    'name' => $staffUser->name,
                    'email' => $staffUser->email,
                ],
                'token' => $token
            ]
        ]);
    }

    /**
     * Handle Global Superadmin authentication.
     */
    public function superadminLogin(Request $request): JsonResponse
    {
        // Superadmin should ONLY be authenticatable on the central domain
        if ($this->tenantManager->hasTenant()) {
            return response()->json([
                'success' => false,
                'message' => 'Superadmin authentication is restricted to the central system domain.'
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = 'login-superadmin:' . Str::lower($validated['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'success' => false,
                'message' => "Too many attempts. Please retry in {$seconds} seconds."
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $superadmin = User::where('email', $validated['email'])->first();

        if (!$superadmin || !Hash::check($validated['password'], $superadmin->password)) {
            RateLimiter::hit($throttleKey, 300);
            return response()->json([
                'success' => false,
                'message' => 'Invalid superadmin credentials.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        RateLimiter::clear($throttleKey);

        // Issue Sanctum token for Superadmin
        $token = $superadmin->createToken('superadmin-api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Superadmin authentication successful.',
            'data' => [
                'user' => [
                    'id' => $superadmin->id,
                    'name' => $superadmin->name,
                    'email' => $superadmin->email,
                ],
                'token' => $token
            ]
        ]);
    }

    /**
     * Revoke authenticated tokens (Logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.'
        ]);
    }

    /**
     * Register trusted device context.
     */
    protected function registerTrustedDevice($user, Request $request): void
    {
        $deviceHash = hash('sha256', $request->ip() . $request->userAgent());

        TrustedDevice::updateOrCreate(
            [
                'user_type' => get_class($user),
                'user_id' => $user->id,
                'device_hash' => $deviceHash,
            ],
            [
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? 'Unknown', 0, 500),
                'last_active_at' => now(),
            ]
        );
    }
}

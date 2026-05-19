<?php

declare(strict_types=1);

namespace App\Core\Settings;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;

class SettingsRegistry
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Get a setting by key, with full cascade resolution and typed casting.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $tenantId = $this->tenantManager->getTenantId();
        $cacheKey = "settings:tenant_" . ($tenantId ?? 'global') . ":key_{$key}";

        return Cache::remember($cacheKey, 600, function () use ($key, $tenantId, $default) {
            $setting = null;

            // 1. Resolve Tenant Override first
            if ($tenantId) {
                $setting = Setting::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('key', $key)->first();
            }

            // 2. Resolve Global platform default next
            if (!$setting) {
                $setting = Setting::withoutGlobalScopes()->whereNull('tenant_id')->where('key', $key)->first();
            }

            // 3. Fallback to default parameter
            if (!$setting) {
                return $default;
            }

            // 4. Decrypt if encrypted
            $rawValue = $setting->value;
            if ($setting->is_encrypted && $rawValue !== null) {
                try {
                    $rawValue = Crypt::decryptString($rawValue);
                } catch (\Throwable $e) {
                    // Fail gracefully
                }
            }

            // 5. Cast typed values
            return $this->castValue($rawValue, $setting->type);
        });
    }

    /**
     * Set/update a setting value, handling encryption and cache clearing.
     */
    public function set(string $key, mixed $value, string $type = 'string', bool $encrypt = false, bool $isGlobal = false): void
    {
        $tenantId = $isGlobal ? null : $this->tenantManager->getTenantId();

        // 1. Cast value to raw string representation for database
        $rawValue = $this->serializeValue($value, $type);

        // 2. Encrypt if specified
        if ($encrypt && $rawValue !== null) {
            $rawValue = Crypt::encryptString($rawValue);
        }

        // 3. Insert or update record
        Setting::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => $rawValue, 'type' => $type, 'is_encrypted' => $encrypt]
        );


        // 4. Clear cache instantly
        $cacheKey = "settings:tenant_" . ($tenantId ?? 'global') . ":key_{$key}";
        Cache::forget($cacheKey);
    }

    /**
     * Cast values to correct native php types.
     */
    protected function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int)$value,
            'array', 'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Serialize native values to DB formats.
     */
    protected function serializeValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'array', 'json' => json_encode($value),
            default => (string)$value,
        };
    }
}

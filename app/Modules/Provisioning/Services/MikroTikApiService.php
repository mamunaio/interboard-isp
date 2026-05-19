<?php

declare(strict_types=1);

namespace App\Modules\Provisioning\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Exception;

class MikroTikApiService
{
    protected string $host;
    protected string $user;
    protected string $password;
    protected bool $verifySsl;

    public function __construct()
    {
        $this->host = config('services.mikrotik.host', '');
        $this->user = config('services.mikrotik.user', '');
        $this->password = config('services.mikrotik.password', '');
        $this->verifySsl = config('services.mikrotik.verify_ssl', false);
    }

    protected function client(): PendingRequest
    {
        if (empty($this->host) || empty($this->user)) {
            throw new Exception("MikroTik connection credentials not configured.");
        }

        return Http::withBasicAuth($this->user, $this->password)
            ->withOptions(['verify' => $this->verifySsl])
            ->baseUrl(rtrim($this->host, '/') . '/rest');
    }

    /**
     * Create a new PPP secret (PPPoE user).
     */
    public function createPppSecret(string $name, string $password, string $profile, string $service = 'pppoe'): array
    {
        $response = $this->client()->put('/ppp/secret', [
            'name' => $name,
            'password' => $password,
            'profile' => $profile,
            'service' => $service,
        ]);

        if ($response->failed()) {
            throw new Exception("Failed to create PPP secret: " . $response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * Update an existing PPP secret.
     */
    public function updatePppSecret(string $name, array $data): array
    {
        // First find the .id
        $id = $this->getPppSecretId($name);

        $response = $this->client()->patch("/ppp/secret/{$id}", $data);

        if ($response->failed()) {
            throw new Exception("Failed to update PPP secret: " . $response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * Disable a PPP secret (Suspend).
     */
    public function disablePppSecret(string $name): array
    {
        $id = $this->getPppSecretId($name);
        
        $response = $this->client()->post("/ppp/secret/{$id}/disable");

        if ($response->failed()) {
            throw new Exception("Failed to disable PPP secret: " . $response->body());
        }

        // Also terminate active connection if it exists
        $this->terminateActiveConnection($name);

        return $response->json() ?? [];
    }

    /**
     * Enable a PPP secret (Reactivate).
     */
    public function enablePppSecret(string $name): array
    {
        $id = $this->getPppSecretId($name);
        
        $response = $this->client()->post("/ppp/secret/{$id}/enable");

        if ($response->failed()) {
            throw new Exception("Failed to enable PPP secret: " . $response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * Remove a PPP secret (Terminate).
     */
    public function removePppSecret(string $name): array
    {
        $id = $this->getPppSecretId($name);
        
        $response = $this->client()->delete("/ppp/secret/{$id}");

        if ($response->failed()) {
            throw new Exception("Failed to remove PPP secret: " . $response->body());
        }

        $this->terminateActiveConnection($name);

        return $response->json() ?? [];
    }

    /**
     * Update speed profile.
     */
    public function updateProfile(string $name, string $profile): array
    {
        return $this->updatePppSecret($name, ['profile' => $profile]);
    }

    /**
     * Helper to get internal ID of a secret by username.
     */
    protected function getPppSecretId(string $name): string
    {
        $response = $this->client()->get('/ppp/secret', [
            'name' => $name,
        ]);

        if ($response->failed()) {
            throw new Exception("Failed to lookup PPP secret: " . $response->body());
        }

        $items = $response->json();
        if (empty($items)) {
            throw new Exception("PPP secret not found for name: {$name}");
        }

        // Return internal .id
        return $items[0]['.id'] ?? $items[0]['id'];
    }

    /**
     * Terminate an active PPPoE connection to enforce immediate suspension.
     */
    public function terminateActiveConnection(string $name): void
    {
        try {
            $response = $this->client()->get('/ppp/active', ['name' => $name]);
            $items = $response->json();
            
            if (!empty($items)) {
                $id = $items[0]['.id'] ?? $items[0]['id'];
                $this->client()->delete("/ppp/active/{$id}");
            }
        } catch (Exception $e) {
            // Silently ignore active connection termination errors
        }
    }
}

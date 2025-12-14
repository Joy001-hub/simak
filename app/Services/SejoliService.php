<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Client\PendingRequest;

/**
 * Sejoli License API Service
 * 
 * Handles communication with Sejoli license server for:
 * - Activation (/sejoli-license/)
 * - Validation (/sejoli-validate-license/)
 * - Reset (/sejoli-delete-license/)
 * 
 * IMPORTANT: Server expects form-urlencoded data, NOT JSON!
 */
class SejoliService
{
    protected string $baseUrl = 'https://kavling.pro';

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.sejoli.base_url', $this->baseUrl), '/');
    }

    /**
     * Create HTTP client with proper configuration
     */
    private function http(): PendingRequest
    {
        $curlOptions = [];
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            $curlOptions[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }

        return Http::withOptions([
            'verify' => false,
            'curl' => $curlOptions,
        ])
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->retry(2, 2000, throw: false);
    }

    /**
     * Get hardware ID for this device
     * Stores device ID in storage/app/device_id.txt
     */
    public function getHardwareID(): string
    {
        $biosUUID = $this->getBiosHardwareID();
        if ($biosUUID) {
            return $biosUUID;
        }

        // Use consistent path with LicenseService
        $path = storage_path('app/device_id.txt');
        if (file_exists($path)) {
            $saved = trim(file_get_contents($path));
            if (!empty($saved)) {
                return $saved;
            }
        }

        $newId = (string) Str::uuid();

        // Ensure directory exists
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $newId);
        return $newId;
    }

    /**
     * Get BIOS hardware UUID (Windows only)
     */
    public function getBiosHardwareID(): ?string
    {
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $output = shell_exec('wmic csproduct get uuid 2>nul');
                if ($output && preg_match('/[A-F0-9-]{36}/i', $output, $matches)) {
                    return strtoupper($matches[0]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[SejoliService] HWID error: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Activate/Register license
     * POST /sejoli-license/
     * 
     * Uses form-urlencoded (asForm) - server doesn't accept JSON!
     */
    public function registerLicense(string $email, string $password, string $licenseKey): ?array
    {
        try {
            $requestData = [
                'user_email' => trim($email),
                'user_pass' => $password,
                'license' => trim($licenseKey),
                'string' => $this->getHardwareID(),
            ];

            Log::info('[Sejoli] Register request', [
                'url' => $this->baseUrl . '/sejoli-license/',
                'email' => $email,
                'license' => $licenseKey,
                'hardware_id' => $requestData['string'],
            ]);

            // IMPORTANT: Use asForm() not asJson() - server expects form data
            $response = $this->http()
                ->asForm()
                ->post($this->baseUrl . '/sejoli-license/', $requestData);

            $body = $response->json();

            Log::info('[Sejoli] Register response', [
                'status' => $response->status(),
                'body' => $body,
            ]);

            return $response->successful() ? $body : null;
        } catch (\Throwable $e) {
            Log::error('[Sejoli] Register failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate license status
     * POST /sejoli-validate-license/
     * 
     * Auth is OPTIONAL but if provided, server will verify ownership
     * String is REQUIRED
     */
    public function validateLicense(
        string $licenseKey,
        ?string $hardwareId = null,
        ?string $token = null,
        ?string $email = null,
        ?string $password = null
    ): ?array {
        try {
            $string = $hardwareId ?: $this->getHardwareID();

            $http = $this->http()->asForm();

            // Add Bearer token if provided
            if ($token) {
                $http = $http->withToken($token);
            }

            // Build request data
            $requestData = [
                'string' => $string,
            ];

            // Add credentials if provided (for verification)
            if ($email && $password) {
                $requestData['user_email'] = trim($email);
                $requestData['user_pass'] = $password;
            }

            // POST with form data
            $response = $http->post($this->baseUrl . '/sejoli-validate-license/', $requestData);

            $body = $response->json();

            Log::info('[Sejoli] Validate response', [
                'license' => $licenseKey,
                'has_credentials' => !empty($email),
                'status' => $response->status(),
                'body' => $body,
            ]);

            return $response->successful() ? $body : null;
        } catch (\Throwable $e) {
            Log::warning('[Sejoli] Validate failed (network issue?): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate license with credentials (forces auth check)
     * This should be used for login to ensure only license owner can access
     */
    public function validateLicenseWithAuth(string $email, string $password, string $licenseKey, ?string $hardwareId = null): ?array
    {
        return $this->validateLicense($licenseKey, $hardwareId, null, $email, $password);
    }

    /**
     * Reset/Delete license from device
     * POST /sejoli-delete-license/
     * 
     * Auth is REQUIRED (Token or email+password)
     */
    public function resetLicense(string $email, string $password, string $licenseKey, ?string $token = null): ?array
    {
        try {
            $http = $this->http()->asForm();

            // Add Bearer token if provided
            if ($token) {
                $http = $http->withToken($token);
            }

            $response = $http->post($this->baseUrl . '/sejoli-delete-license/', [
                'user_email' => trim($email),
                'user_pass' => $password,
                'license' => trim($licenseKey),
                'string' => $this->getHardwareID(),
            ]);

            $body = $response->json();

            Log::info('[Sejoli] Reset response', [
                'license' => $licenseKey,
                'status' => $response->status(),
                'body' => $body,
            ]);

            return $response->successful() ? $body : null;
        } catch (\Throwable $e) {
            Log::error('[Sejoli] Reset failed: ' . $e->getMessage());
            return null;
        }
    }
}
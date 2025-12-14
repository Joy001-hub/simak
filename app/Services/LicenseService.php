<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * License Service - Local license management
 * 
 * Handles local storage of license data and communication with SejoliService.
 * Updated to work with Sejoli Custom Extensions v2.1.0 API responses.
 */
class LicenseService
{
    protected $licenseFile;
    protected $licenseDir;
    protected $jsonLicenseFile;

    public function __construct()
    {
        $this->licenseDir = storage_path('app/license');
        $this->licenseFile = $this->licenseDir . '/license.dat';
        $this->jsonLicenseFile = $this->licenseDir . '/license.json';
    }

    /**
     * Generate a local license key (for offline use)
     */
    public function generateLicense(string $clientName, string $clientEmail): string
    {
        $timestamp = time();
        $hash = hash('sha256', $clientName . $clientEmail . $timestamp);
        $segment1 = strtoupper(substr($hash, 0, 4));
        $segment2 = strtoupper(substr($hash, 4, 4));
        $segment3 = strtoupper(substr($hash, 8, 4));
        $segment4 = strtoupper(substr($hash, 12, 4));
        return "SIMAK-{$segment1}-{$segment2}-{$segment3}-{$segment4}";
    }

    /**
     * Save license with encryption
     */
    public function saveLicense(string $licenseKey, string $clientName, string $clientEmail): bool
    {
        if (!File::exists($this->licenseDir)) {
            File::makeDirectory($this->licenseDir, 0755, true);
        }

        $licenseData = [
            'license_key' => $licenseKey,
            'client' => $clientName,
            'email' => $clientEmail,
            'installed_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'status' => 'active',
        ];

        File::put($this->jsonLicenseFile, json_encode($licenseData));
        $encrypted = Crypt::encryptString(json_encode($licenseData));
        return File::put($this->licenseFile, $encrypted) !== false;
    }

    /**
     * Save license data to local JSON file
     */
    public function saveLocalLicense(array $payload): bool
    {
        if (!File::exists($this->licenseDir)) {
            File::makeDirectory($this->licenseDir, 0755, true);
        }

        // Generate signature for integrity check
        if (!empty($payload['license_key']) && !empty($payload['hardware_id'])) {
            $payload['signature'] = hash_hmac(
                'sha256',
                $payload['license_key'] . '|' . $payload['hardware_id'],
                (string) config('app.key')
            );
        }

        $json = json_encode($payload);

        try {
            if (!File::exists(dirname($this->jsonLicenseFile))) {
                File::makeDirectory(dirname($this->jsonLicenseFile), 0755, true);
            }
            return File::put($this->jsonLicenseFile, $json) !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Load license data from local JSON file
     */
    public function loadLocalLicense(): ?array
    {
        if (File::exists($this->jsonLicenseFile)) {
            try {
                $data = json_decode(File::get($this->jsonLicenseFile), true);
                if (is_array($data) && !empty($data)) {
                    // Verify signature if present
                    if (!empty($data['license_key']) && !empty($data['hardware_id'])) {
                        $expected = hash_hmac(
                            'sha256',
                            $data['license_key'] . '|' . $data['hardware_id'],
                            (string) config('app.key')
                        );
                        $signature = $data['signature'] ?? null;
                        if ($signature && hash_equals($expected, (string) $signature)) {
                            return $data;
                        }
                    }
                    // Return data even without signature for backwards compatibility
                    return $data;
                }
            } catch (\Throwable $e) {
                // Fall through to encrypted file
            }
        }

        // Try encrypted file as fallback
        if (File::exists($this->licenseFile)) {
            try {
                $decrypted = Crypt::decryptString(File::get($this->licenseFile));
                $data = json_decode($decrypted, true);
                if (is_array($data) && !empty($data)) {
                    return $data;
                }
            } catch (\Throwable $e) {
                // Corrupted file
            }
        }

        return null;
    }

    /**
     * Validate local license
     */
    public function validateLicense(): array
    {
        $licenseData = $this->loadLocalLicense();

        if (empty($licenseData)) {
            return [
                'valid' => false,
                'message' => 'License not found. Please activate your license.',
                'status' => 'not_found',
            ];
        }

        $status = $licenseData['status'] ?? 'inactive';
        if ($status !== 'active') {
            return [
                'valid' => false,
                'message' => 'License is not active.',
                'status' => $status,
            ];
        }

        $expiresAt = $licenseData['expires_at'] ?? null;
        if ($expiresAt && strtotime($expiresAt) < time()) {
            return [
                'valid' => false,
                'message' => 'License has expired. Please renew your license.',
                'status' => 'expired',
                'expires_at' => $expiresAt,
            ];
        }

        return [
            'valid' => true,
            'message' => 'License is valid.',
            'status' => 'active',
            'client' => $licenseData['client'] ?? null,
            'email' => $licenseData['email'] ?? null,
            'expires_at' => $expiresAt,
            'key' => $licenseData['license_key'] ?? null,
        ];
    }

    /**
     * Get license info
     */
    public function getLicenseInfo(): ?array
    {
        return $this->loadLocalLicense();
    }

    /**
     * Revoke local license
     */
    public function revokeLocalLicense(): bool
    {
        return $this->revokeLicense();
    }

    /**
     * Block local license
     */
    public function blockLocalLicense(string $message): bool
    {
        $existing = $this->loadLocalLicense() ?? [];
        return $this->saveLocalLicense(array_merge($existing, [
            'status' => 'blocked',
            'message' => $message,
            'blocked_at' => now()->toIso8601String(),
        ]));
    }

    /**
     * Get hardware ID for this device
     */
    public function getHardwareId(): string
    {
        try {
            return app(SejoliService::class)->getHardwareID();
        } catch (\Throwable $e) {
            $path = storage_path('app/device_id.txt');
            if (File::exists($path)) {
                return trim((string) File::get($path));
            }
            $newId = (string) Str::uuid();
            File::put($path, $newId);
            return $newId;
        }
    }

    /**
     * Activate license via Sejoli server
     */
    public function activate(string $email, string $password, string $licenseKey): ?array
    {
        return app(SejoliService::class)->registerLicense($email, $password, $licenseKey);
    }

    /**
     * Validate license via Sejoli server
     */
    public function validateRemote(string $licenseKey, ?string $hardwareId = null): ?array
    {
        return app(SejoliService::class)->validateLicense($licenseKey, $hardwareId);
    }

    /**
     * Reset license via Sejoli server
     */
    public function resetRemote(string $email, string $password, string $licenseKey): ?array
    {
        return app(SejoliService::class)->resetLicense($email, $password, $licenseKey);
    }

    /**
     * Check if message contains specific strings
     */
    public function messageContains($payload, string|array $needles): bool
    {
        if (!is_array($payload)) {
            return false;
        }

        $needles = (array) $needles;
        $message = $payload['message'] ?? '';
        $messages = $payload['messages'] ?? [];

        if (is_string($messages)) {
            $messages = [$messages];
        }

        $haystacks = [];
        if (is_string($message)) {
            $haystacks[] = $message;
        }
        foreach ($messages as $msg) {
            if (is_string($msg)) {
                $haystacks[] = $msg;
            }
        }

        foreach ($haystacks as $text) {
            foreach ($needles as $needle) {
                if (stripos($text, (string) $needle) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if remote API response is valid
     * 
     * Updated to match Sejoli Custom Extensions v2.1.0 response format:
     * - Response: { valid: bool, message: string, data?: object }
     * - Activation success: valid=true, message="Aktivasi berhasil"
     * - Validation success: valid=true, message="Lisensi valid"
     * - Reset success: valid=true, message="Reset lisensi berhasil"
     */
    public function isRemoteValid($payload, string $licenseKey, string $mode = 'validate'): bool
    {
        if (!is_array($payload)) {
            return false;
        }

        // Primary check: the 'valid' field
        $validFlag = filter_var($payload['valid'] ?? false, FILTER_VALIDATE_BOOLEAN);

        switch ($mode) {
            case 'activate':
                // Activation is valid if valid=true
                // Old format checked for "is registered to" message, new format uses "Aktivasi berhasil"
                if ($validFlag) {
                    // New format: just check valid=true
                    return true;
                }
                // Fallback: check for legacy "is registered to" message
                $message = $payload['message'] ?? '';
                return stripos($message, 'is registered to') !== false ||
                    stripos($message, 'Aktivasi berhasil') !== false;

            case 'reset':
                // Reset is valid if valid=true
                // Old format checked for "telah dihapus", new format uses "Reset lisensi berhasil"
                if ($validFlag) {
                    return true;
                }
                // Fallback: check for legacy message
                $message = $payload['message'] ?? '';
                return stripos($message, 'telah dihapus') !== false ||
                    stripos($message, 'Reset lisensi berhasil') !== false;

            case 'validate':
            default:
                // Validation is valid if valid=true
                // New format: valid=true, message="Lisensi valid"
                // Also check subscription_status if available
                if ($validFlag) {
                    // Double-check subscription status if data is present
                    $data = $payload['data'] ?? [];
                    $subscriptionStatus = $data['subscription_status'] ?? 'active';

                    // Subscription expired = not valid
                    if ($subscriptionStatus === 'expired') {
                        return false;
                    }

                    return true;
                }
                return false;
        }
    }

    /**
     * Revoke (delete) local license files
     */
    public function revokeLicense(): bool
    {
        $deleted = true;
        if (File::exists($this->jsonLicenseFile)) {
            $deleted = File::delete($this->jsonLicenseFile) && $deleted;
        }
        if (File::exists($this->licenseFile)) {
            $deleted = File::delete($this->licenseFile) && $deleted;
        }
        return $deleted;
    }
}
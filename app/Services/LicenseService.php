<?php
namespace App\Services;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
class LicenseService
{
    protected $licenseFile;
    protected $licenseDir;
    protected $jsonLicenseFile;
    protected $jsonLicenseFilePrivate;
    public function __construct()
    {
        $this->licenseDir = storage_path('app/license');
        $this->licenseFile = $this->licenseDir . '/license.dat';
        $this->jsonLicenseFile = $this->licenseDir . '/license.json';
        $this->jsonLicenseFilePrivate = $this->jsonLicenseFile;
    }
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
    public function saveLicense(string $licenseKey, string $clientName, string $clientEmail): bool
    {
        if (!File::exists($this->licenseDir)) {
            File::makeDirectory($this->licenseDir, 0755, true);
        }
        $licenseData = ['license_key' => $licenseKey, 'client' => $clientName, 'email' => $clientEmail, 'installed_at' => date('Y-m-d H:i:s'), 'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')), 'status' => 'active',];
        File::put($this->jsonLicenseFile, json_encode($licenseData));
        $encrypted = Crypt::encryptString(json_encode($licenseData));
        return File::put($this->licenseFile, $encrypted) !== false;
    }
    public function saveLocalLicense(array $payload): bool
    {
        if (!File::exists($this->licenseDir)) {
            File::makeDirectory($this->licenseDir, 0755, true);
        }
        if (!empty($payload['license_key']) && !empty($payload['hardware_id'])) {
            $payload['signature'] = hash_hmac('sha256', $payload['license_key'] . '|' . $payload['hardware_id'], (string) config('app.key'));
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
    public function loadLocalLicense(): ?array
    {
        $paths = [$this->jsonLicenseFile,];
        foreach ($paths as $path) {
            if (File::exists($path)) {
                try {
                    $data = json_decode(File::get($path), true);
                    if (is_array($data) && !empty($data)) {
                        if (!empty($data['license_key']) && !empty($data['hardware_id'])) {
                            $expected = hash_hmac('sha256', $data['license_key'] . '|' . $data['hardware_id'], (string) config('app.key'));
                            $signature = $data['signature'] ?? null;
                            if (!$signature || !hash_equals($expected, (string) $signature)) {
                                continue;
                            }
                        } else {
                            continue;
                        }
                        return $data;
                    }
                } catch (\Throwable $e) {
                }
            }
        }
        if (File::exists($this->licenseFile)) {
            try {
                $decrypted = Crypt::decryptString(File::get($this->licenseFile));
                $data = json_decode($decrypted, true);
                if (is_array($data) && !empty($data)) {
                    return $data;
                }
            } catch (\Throwable $e) {
            }
        }
        return null;
    }
    public function validateLicense(): array
    {
        if (!File::exists($this->jsonLicenseFile) && File::exists($this->jsonLicenseFilePrivate)) {
            try {
                File::copy($this->jsonLicenseFilePrivate, $this->jsonLicenseFile);
            } catch (\Throwable $e) {
            }
        }
        if (File::exists($this->jsonLicenseFile)) {
            try {
                $json = File::get($this->jsonLicenseFile);
                $licenseData = json_decode($json, true);
                if (empty($licenseData)) {
                    throw new \RuntimeException('Empty or invalid license.json');
                }
                $status = $licenseData['status'] ?? 'inactive';
                if ($status !== 'active') {
                    return ['valid' => false, 'message' => 'License is not active.', 'status' => 'inactive',];
                }
                $expiresAt = $licenseData['expires_at'] ?? null;
                if ($expiresAt && strtotime($expiresAt) < time()) {
                    return ['valid' => false, 'message' => 'License has expired. Please renew your license.', 'status' => 'expired', 'expires_at' => $expiresAt,];
                }
                return ['valid' => true, 'message' => 'License is valid.', 'status' => 'active', 'client' => $licenseData['client'] ?? null, 'email' => $licenseData['email'] ?? null, 'expires_at' => $expiresAt, 'key' => $licenseData['license_key'] ?? null,];
            } catch (\Throwable $e) {
            }
        }
        if (!File::exists($this->licenseFile)) {
            return ['valid' => false, 'message' => 'License not found. Please activate your license.', 'status' => 'not_found',];
        }
        try {
            $encrypted = File::get($this->licenseFile);
            $decrypted = Crypt::decryptString($encrypted);
            $licenseData = json_decode($decrypted, true);
            if (!$licenseData) {
                return ['valid' => false, 'message' => 'Invalid license file.', 'status' => 'invalid',];
            }
            if ($licenseData['status'] !== 'active') {
                return ['valid' => false, 'message' => 'License is not active.', 'status' => 'inactive',];
            }
            if (strtotime($licenseData['expires_at']) < time()) {
                return ['valid' => false, 'message' => 'License has expired. Please renew your license.', 'status' => 'expired', 'expires_at' => $licenseData['expires_at'],];
            }
            return ['valid' => true, 'message' => 'License is valid.', 'status' => 'active', 'client' => $licenseData['client'], 'email' => $licenseData['email'], 'expires_at' => $licenseData['expires_at'], 'key' => $licenseData['key'],];
        } catch (\Exception $e) {
            return ['valid' => false, 'message' => 'Error validating license: ' . $e->getMessage(), 'status' => 'error',];
        }
    }
    public function getLicenseInfo(): ?array
    {
        if (File::exists($this->jsonLicenseFile)) {
            try {
                return json_decode(File::get($this->jsonLicenseFile), true);
            } catch (\Throwable $e) {
            }
        }
        if (File::exists($this->licenseFile)) {
            try {
                $encrypted = File::get($this->licenseFile);
                $decrypted = Crypt::decryptString($encrypted);
                return json_decode($decrypted, true);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }
    public function revokeLocalLicense(): bool
    {
        return $this->revokeLicense();
    }
    public function blockLocalLicense(string $message): bool
    {
        $existing = $this->loadLocalLicense() ?? [];
        return $this->saveLocalLicense(array_merge($existing, ['status' => 'blocked', 'message' => $message, 'blocked_at' => now()->toIso8601String(),]));
    }
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
    public function activate(string $email, string $password, string $licenseKey): ?array
    {
        return app(SejoliService::class)->registerLicense($email, $password, $licenseKey);
    }
    public function validateRemote(string $licenseKey, ?string $hardwareId = null): ?array
    {
        return app(SejoliService::class)->validateLicense($licenseKey, $hardwareId);
    }
    public function resetRemote(string $email, string $password, string $licenseKey): ?array
    {
        return app(SejoliService::class)->resetLicense($email, $password, $licenseKey);
    }
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
    public function isRemoteValid($payload, string $licenseKey, string $mode = 'validate'): bool
    {
        if (!is_array($payload)) {
            return false;
        }
        $successFlag = filter_var($payload['success'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $validFlag = filter_var($payload['valid'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $statusFlag = isset($payload['status']) && strcasecmp((string) $payload['status'], 'success') === 0;
        $resultFlag = isset($payload['result']) && strcasecmp((string) $payload['result'], 'success') === 0;
        $licenseCandidates = [$payload['license'] ?? null, $payload['license_key'] ?? null, $payload['license_code'] ?? null, $payload['data']['license'] ?? null, $payload['data']['license_key'] ?? null, $payload['detail']['license'] ?? null, $payload['detail']['license_key'] ?? null,];
        $licenseMatches = true;
        $hasLicenseField = false;
        foreach ($licenseCandidates as $candidate) {
            if ($candidate !== null) {
                $hasLicenseField = true;
                $licenseMatches = strcasecmp(trim((string) $candidate), $licenseKey) === 0;
                if ($licenseMatches)
                    break;
            }
        }
        if ($hasLicenseField && !$licenseMatches) {
            return false;
        }
        $hasPositive = $successFlag || $validFlag || $statusFlag || $resultFlag;
        if (array_key_exists('success', $payload) && !$successFlag)
            return false;
        if (array_key_exists('valid', $payload) && !$validFlag)
            return false;
        if (isset($payload['status']) && !$statusFlag)
            return false;
        if (isset($payload['result']) && !$resultFlag)
            return false;
        if (!$hasPositive) {
            return false;
        }
        $message = $payload['message'] ?? '';
        $messages = $payload['messages'] ?? [];
        if (is_string($messages)) {
            $messages = [$messages];
        }
        switch ($mode) {
            case 'activate':
                if ($validFlag) {
                    return true;
                }
                $hasRegisteredMsg = false;
                if (is_string($message) && stripos($message, 'is registered to') !== false) {
                    $hasRegisteredMsg = true;
                }
                foreach ($messages as $msg) {
                    if (is_string($msg) && stripos($msg, 'is registered to') !== false) {
                        $hasRegisteredMsg = true;
                        break;
                    }
                }
                return $hasRegisteredMsg;
            case 'reset':
                return $validFlag && is_string($message) && stripos($message, 'telah dihapus') !== false;
            case 'validate':
            default:
                if (!$validFlag) {
                    return false;
                }
                if ($hasLicenseField && !$licenseMatches) {
                    return false;
                }
                if (!$hasLicenseField) {
                    $local = $this->loadLocalLicense();
                    $localKey = $local['license_key'] ?? null;
                    if (!$localKey) {
                        return false;
                    }
                    if (strcasecmp(trim((string) $localKey), $licenseKey) !== 0) {
                        return false;
                    }
                }
                return is_string($message) && trim($message) === '';
        }
    }
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
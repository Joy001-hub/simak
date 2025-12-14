<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Client\PendingRequest;
class SejoliService
{
    protected string $baseUrl = 'https://member.juragankavling.web.id';
    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.sejoli.base_url', $this->baseUrl), '/');
    }
    private function http(): PendingRequest
    {
        $curlOptions = [];
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            $curlOptions[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }
        return Http::withOptions(['verify' => false, 'curl' => $curlOptions,])->acceptJson()->connectTimeout(8)->timeout(25)->retry(3, 1500, throw: false);
    }
    public function getHardwareID(): string
    {
        $biosUUID = $this->getBiosHardwareID();
        if ($biosUUID)
            return $biosUUID;
        $path = 'device_id.txt';
        if (Storage::disk('local')->exists($path)) {
            $saved = trim(Storage::disk('local')->get($path));
            if (!empty($saved))
                return $saved;
        }
        $newId = (string) Str::uuid();
        Storage::disk('local')->put($path, $newId);
        return $newId;
    }
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
    public function registerLicense(string $email, string $password, string $licenseKey): ?array
    {
        try {
            $response = $this->http()->asForm()->post($this->baseUrl . '/sejoli-license/', ['user_email' => trim($email), 'user_pass' => $password, 'license' => trim($licenseKey), 'string' => $this->getHardwareID(),]);
            return $response->successful() ? $response->json() : null;
        } catch (\Throwable $e) {
            Log::error('[Sejoli] Register failed: ' . $e->getMessage());
            return null;
        }
    }
    public function validateLicense(string $licenseKey, ?string $hardwareId = null, ?string $email = null, ?string $password = null): ?array
    {
        try {
            $string = $hardwareId ?: $this->getHardwareID();
            $data = [
                'license' => trim($licenseKey),
                'string' => $string,
            ];

            if ($email) {
                $data['user_email'] = trim($email);
            }
            if ($password) {
                $data['user_pass'] = $password;
            }

            $response = $this->http()->asForm()->post($this->baseUrl . '/sejoli-validate-license/', $data);

            return $response->successful() ? $response->json() : null;
        } catch (\Throwable $e) {
            Log::warning('[Sejoli] Validate failed (network issue?): ' . $e->getMessage());
            return null;
        }
    }
    public function resetLicense(string $email, string $password, string $licenseKey): ?array
    {
        try {
            $response = $this->http()->asForm()->post($this->baseUrl . '/sejoli-delete-license/', ['user_email' => trim($email), 'user_pass' => $password, 'license' => trim($licenseKey), 'string' => $this->getHardwareID(),]);
            return $response->successful() ? $response->json() : null;
        } catch (\Throwable $e) {
            Log::error('[Sejoli] Reset failed: ' . $e->getMessage());
            return null;
        }
    }
}
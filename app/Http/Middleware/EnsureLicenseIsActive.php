<?php

namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware untuk memastikan lisensi aktif dan user sudah login.
 * 
 * Flow:
 * 1. Cek session 'license_authenticated' - harus true
 * 2. Jika tidak ada session, redirect ke login
 * 3. Session di-set oleh LicenseController::processAuthLogin setelah validasi server berhasil
 */
class EnsureLicenseIsActive
{
    protected LicenseService $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    public function handle(Request $request, Closure $next)
    {
        // Check if user has authenticated via license login
        if (!session('license_authenticated')) {
            // Not authenticated - check if license exists locally
            $license = $this->licenseService->loadLocalLicense();

            if (!$license || empty($license['license_key'])) {
                // No license at all - go to activation
                return redirect()->route('license.activate.form')
                    ->withErrors(['msg' => 'Lisensi tidak ditemukan. Silakan aktivasi terlebih dahulu.']);
            }

            // License exists but not authenticated - go to login
            return redirect()->route('login')
                ->withErrors(['msg' => 'Silakan login terlebih dahulu.']);
        }

        // Check license status is still valid
        $license = $this->licenseService->loadLocalLicense();

        if (!$license || ($license['status'] ?? 'inactive') !== 'active') {
            // License not active - clear session and redirect
            session()->forget('license_authenticated');
            session()->forget('license_user_email');

            return redirect()->route('license.activate.form')
                ->withErrors(['msg' => 'Lisensi tidak aktif. Silakan aktivasi ulang.']);
        }

        return $next($request);
    }
}
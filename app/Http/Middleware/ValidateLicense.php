<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Cache;
use Closure;
use Illuminate\Http\Request;
use App\Services\LicenseService;

/**
 * Middleware ValidateLicense
 * BYPASSED: Validasi lisensi dinonaktifkan untuk integrasi dengan sistem baru.
 */
class ValidateLicense
{
    protected $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    public function handle(Request $request, Closure $next)
    {
        // BYPASS: Hapus semua lock dan selalu izinkan akses
        if (Cache::has('app_offline_lock')) {
            Cache::forget('app_offline_lock');
        }

        return $next($request);

        // === KODE ORIGINAL (DINONAKTIFKAN) ===
        // if (Cache::has('app_offline_lock')) {
        //     if (!$request->routeIs('license.activate.*')) {
        //         return redirect()->route('license.activate.form')
        //             ->with('error', 'Validasi Mingguan Gagal.');
        //     }
        // }
        //
        // $license = $this->licenseService->loadLocalLicense();
        // $isActive = $license && ($license['status'] ?? 'blocked') === 'active';
        //
        // if (!$isActive) {
        //     $payload = [
        //         'valid' => false,
        //         'message' => $license['message'] ?? 'Lisensi tidak valid.',
        //         'status' => $license['status'] ?? 'blocked',
        //     ];
        //
        //     if ($request->expectsJson()) {
        //         return response()->json($payload, 403);
        //     }
        //
        //     return redirect()->route('license.activate.form')
        //         ->with('error', $payload['message']);
        // }
        //
        // return $next($request);
    }
}
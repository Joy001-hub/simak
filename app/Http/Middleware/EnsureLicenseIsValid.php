<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Middleware untuk validasi lisensi.
 * BYPASSED: Validasi mingguan dinonaktifkan untuk integrasi dengan sistem lisensi baru.
 */
class EnsureLicenseIsValid
{
    public function handle(Request $request, Closure $next)
    {
        // BYPASS: Hapus lock jika ada dan selalu izinkan akses
        if (Cache::has('app_offline_lock')) {
            Cache::forget('app_offline_lock');
        }

        return $next($request);

        // === KODE ORIGINAL (DINONAKTIFKAN) ===
        // if ($request->routeIs('license.locked') || $request->routeIs('license.blocked')) {
        //     return $next($request);
        // }
        //
        // if (Cache::has('app_offline_lock')) {
        //     if ($this->hasInternet()) {
        //         Cache::forget('app_offline_lock');
        //     } else {
        //         return redirect()->route('license.locked');
        //     }
        // }
        //
        // return $next($request);
    }

    private function hasInternet(): bool
    {
        try {
            $resp = Http::timeout(3)->withoutVerifying()
                ->get('https://www.google.com/generate_204');
            return $resp->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
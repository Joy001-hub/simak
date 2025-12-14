<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware CheckLicenseStatus
 * BYPASSED: Validasi lisensi dinonaktifkan.
 */
class CheckLicenseStatus
{
    public function handle(Request $request, Closure $next)
    {
        // BYPASS: Selalu izinkan akses
        return $next($request);
    }
}
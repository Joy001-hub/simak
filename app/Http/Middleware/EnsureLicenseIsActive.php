<?php
namespace App\Http\Middleware;
use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
class EnsureLicenseIsActive
{
    protected LicenseService $licenseService;
    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }
    public function handle(Request $request, Closure $next)
    {
        if (
            $request->routeIs('license.activate.form') ||
            $request->routeIs('license.activate') ||
            $request->routeIs('login') ||
            $request->routeIs('auth.login') ||
            $request->routeIs('reset') ||
            $request->routeIs('auth.reset') ||
            $request->routeIs('license.reset') ||
            $request->routeIs('license.revalidate') ||
            $request->routeIs('license.blocked') ||
            $request->routeIs('license.locked') ||
            $request->routeIs('native-img.*')
        ) {
            return $next($request);
        }

        $license = $this->licenseService->loadLocalLicense();
        if (!$license || empty($license['license_key']) || empty($license['hardware_id'])) {
            return redirect()->route('license.activate.form')
                ->withErrors(['msg' => 'Lisensi tidak ditemukan atau file lisensi rusak. Silakan aktivasi ulang.']);
        }
        if (($license['status'] ?? 'blocked') !== 'active') {
            $message = $license['message'] ?? 'Lisensi dibekukan karena gagal validasi mingguan. Pastikan internet aktif dan lakukan validasi ulang.';
            return redirect()->route('license.blocked')->withErrors(['msg' => $message]);
        }
        return $next($request);
    }
}
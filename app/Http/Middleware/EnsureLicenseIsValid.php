<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
class EnsureLicenseIsValid
{
    public function handle(Request $request, Closure $next)
    {
        if (
            $request->routeIs('license.locked') ||
            $request->routeIs('license.blocked') ||
            $request->routeIs('license.activate.form') ||
            $request->routeIs('license.activate') ||
            $request->routeIs('login') ||
            $request->routeIs('auth.login') ||
            $request->routeIs('reset') ||
            $request->routeIs('auth.reset') ||
            $request->routeIs('license.reset') ||
            $request->routeIs('native-img.*')
        ) {
            return $next($request);
        }

        if (Cache::has('app_offline_lock')) {
            if ($this->hasInternet()) {
                Cache::forget('app_offline_lock');
            } else {
                return redirect()->route('license.locked');
            }
        }
        return $next($request);
    }
    private function hasInternet(): bool
    {
        try {
            $resp = Http::timeout(3)->withoutVerifying()->get('https://member.juragankavling.web.id/generate_204');
            if ($resp->successful()) {
                return true;
            }
        } catch (\Throwable $e) {
        }
        try {
            $resp = Http::timeout(3)->withoutVerifying()->get('https://www.google.com/generate_204');
            return $resp->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
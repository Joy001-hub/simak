<?php

namespace App\Providers;

use App\Console\Commands\NativeConfigFallback;
use App\Console\Commands\NativePhpIniFallback;
use App\Models\CompanyProfile;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                NativeConfigFallback::class,
                NativePhpIniFallback::class,
            ]);
        }

        // Share company profile, logo, and overdue payments across views
        try {
            View::composer('*', function ($view) {
                $companyProfile = Schema::hasTable('company_profiles') ? CompanyProfile::first() : null;
                $logoConfigPath = config('company.logo_url', '/logo-app.png');
                $logoPath = asset($logoConfigPath);
                $version = null;

                if ($companyProfile?->logo_path && Storage::disk('public')->exists($companyProfile->logo_path)) {
                    $logoPath = Storage::url($companyProfile->logo_path);
                    $version = Storage::disk('public')->lastModified($companyProfile->logo_path);
                } else {
                    $defaultLogoFile = public_path(ltrim($logoConfigPath, '/'));
                    if (is_file($defaultLogoFile)) {
                        $version = filemtime($defaultLogoFile);
                    }
                }

                if ($version) {
                    $logoPath .= (str_contains($logoPath, '?') ? '&' : '?') . 'v=' . $version;
                }

                $overdueCount = 0;
                $overduePayments = collect();
                if (Schema::hasTable('payments')) {
                    $overdueQuery = Payment::with(['sale.buyer'])
                        ->where('status', 'unpaid')
                        ->whereDate('due_date', '<', Carbon::today());
                    $overdueCount = $overdueQuery->count();
                    $overduePayments = $overdueQuery->orderBy('due_date', 'asc')->get();
                }

                $view->with('companyProfile', $companyProfile)
                    ->with('companyLogo', $logoPath)
                    ->with('overdueCount', $overdueCount)
                    ->with('overduePayments', $overduePayments);
            });
        } catch (\Exception $e) {
            // Avoid breaking app during early migrations
        }
    }
}

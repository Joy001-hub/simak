<?php
namespace App\Providers;
use Native\Laravel\Facades\Window;
use Native\Laravel\Contracts\ProvidesPhpIni;
class NativeAppServiceProvider implements ProvidesPhpIni
{
    public function boot(): void
    {
        Window::open()
            ->title(config('app.name', 'Simak'))
            ->maximized()
            ->hideMenu()
            ->focusable()
            ->webPreferences([
                'acceptFirstMouse' => true,
            ])
            ->icon(public_path('icon.png'));
    }
    public function phpIni(): array
    {
        return [];
    }
}

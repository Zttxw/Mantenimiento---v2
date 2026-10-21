<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // El Coordinador OTI / Superadmin tiene acceso total
        Gate::before(function ($user, string $ability) {
            if ($user->hasRole('coordinador_oti') || $user->hasRole('admin')) {
                return true;
            }
        });
    }
}

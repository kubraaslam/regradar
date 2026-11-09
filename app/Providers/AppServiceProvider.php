<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Used by views to hide actions a read-only Developer cannot take. The
        // routes enforce the same rule independently, so hiding a button is a
        // courtesy rather than the security boundary.
        Gate::define('manage', fn(User $user) => $user->canManage());
    }
}

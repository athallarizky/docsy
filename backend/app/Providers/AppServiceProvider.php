<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Model-less resources: policies don't apply, Gates do
        Gate::define('view-dashboard', fn(User $user) => $user->isAdmin());
        Gate::define('view-activity-logs', fn(User $user) => $user->isAdmin());
    }
}

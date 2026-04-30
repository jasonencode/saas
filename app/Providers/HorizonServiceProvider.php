<?php

namespace App\Providers;

use App\Models\System\Administrator;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    protected function gate(): void
    {
        Gate::define('viewHorizon', static fn ($user = null) => $user instanceof Administrator && $user->isAdministrator());
    }
}

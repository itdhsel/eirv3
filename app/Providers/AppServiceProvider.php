<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\IncidentReport;
use App\Policies\IncidentReportPolicy;
use Illuminate\Support\Facades\Gate;

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
        Gate::policy(IncidentReport::class, IncidentReportPolicy::class);
    }
}
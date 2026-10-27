<?php

namespace App\Providers;

use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Event;
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
        // On Heroku, start the worker dyno whenever something is queued (it scales itself back down when idle)
        Event::listen(JobQueued::class, fn() => app(\App\Services\HerokuWorkerScaler::class)->ensureRunning());
    }
}

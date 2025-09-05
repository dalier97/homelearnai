<?php

namespace App\Providers;

use App\Services\CacheService;
use App\Services\IcsImportService;
use App\Services\QualityHeuristicsService;
use App\Services\SchedulingEngine;
use App\Services\SupabaseClient;
use Illuminate\Support\ServiceProvider;

class SupabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SupabaseClient::class, function ($app) {
            return new SupabaseClient(
                config('services.supabase.url'),
                config('services.supabase.anon_key'),
                config('services.supabase.service_key')
            );
        });

        $this->app->singleton(SchedulingEngine::class, function ($app) {
            return new SchedulingEngine($app->make(SupabaseClient::class));
        });

        $this->app->singleton(IcsImportService::class, function ($app) {
            return new IcsImportService($app->make(SupabaseClient::class));
        });

        $this->app->singleton(QualityHeuristicsService::class, function ($app) {
            return new QualityHeuristicsService($app->make(SupabaseClient::class));
        });

        $this->app->singleton(CacheService::class, function ($app) {
            return new CacheService;
        });
    }

    public function boot(): void
    {
        //
    }
}

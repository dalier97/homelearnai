<?php

namespace App\Providers;

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
    }

    public function boot(): void
    {
        //
    }
}

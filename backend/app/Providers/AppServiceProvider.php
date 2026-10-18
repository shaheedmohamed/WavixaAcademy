<?php

namespace App\Providers;

use App\Services\MuxVideoProvider;
use App\Services\VideoProviderInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VideoProviderInterface::class, function () {
            return match (config('services.mux.provider', 'mux')) {
                default => new MuxVideoProvider,
            };
        });
    }

    public function boot(): void
    {
        //
    }
}

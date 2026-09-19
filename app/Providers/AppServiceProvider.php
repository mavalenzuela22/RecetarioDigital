<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Application bindings will be added with the first bounded domain task.
    }

    public function boot(): void
    {
        // Runtime defaults remain in configuration for the IIS deployment target.
    }
}

<?php

namespace App\Providers;

use App\Http\Middleware\SetLocale;
use App\View\Composers\SidebarComposer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('layouts.sidebar', SidebarComposer::class);

        // Share locale data with all views
        View::share('currentLocale', fn() => App::getLocale());
        View::share('supportedLocales', SetLocale::SUPPORTED);
    }
}

<?php

namespace App\Providers;

use App\Services\Engagement\ApplicationChrome;
use App\Services\Publishing\AccessSettings;
use Illuminate\Support\Facades\View;
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
        View::composer(['layouts.account', 'layouts.learning'], function ($view): void {
            $view->with('chrome', app(ApplicationChrome::class)->snapshot(request()));
        });
        View::composer(['content.editor', 'content.course-editor', 'challenges.editor'], function ($view): void {
            $view->with('accessModules', app(AccessSettings::class)->options(auth()->user()));
        });
    }
}

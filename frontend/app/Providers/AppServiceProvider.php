<?php

namespace App\Providers;

use App\Services\RequestMetrics;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(RequestMetrics::class, fn () => new RequestMetrics());
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Map polymorphic types to short names for backward compatibility
        // This allows bounding_boxes table to use short names like "TypoError" instead of full class names
        Relation::enforceMorphMap([
            'TypoError' => \App\Models\TypoError::class,
            'PriceValidation' => \App\Models\PriceValidation::class,
            'DateValidation' => \App\Models\DateValidation::class,
        ]);
    }
}

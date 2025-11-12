<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Password;
// use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Request;

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
        // Password::defaults(function(){
        //     return Password::min(8)
        //         ->mixedCase()
        //         ->numbers()
        //         ->symbols();
        // });

        // $this->configureRateLimiting();
    }
    
    // protected function configureRateLimiting(Request $request):void
    // {
    //     RateLimiter::for('api', function (Request $request) {
    //         return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    //     });
    //     RateLimiter::for('auth', function (Request $request) {
    //         return Limit::perMinute(5)->by($request->ip());
    //     });
    // }
}

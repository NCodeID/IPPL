<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

$now = Carbon::now()->floorSeconds();

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

        $now = Carbon::now()->setTime(
            Carbon::now()->hour,
            Carbon::now()->minute,
            Carbon::now()->second,
            0 
        );

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(5)
                ->by($email.$request->ip())
                ->response(function (Request $request, array $headers) {
                    $seconds = $headers['Retry-After'] ?? 60;

                    return response()->json([
                        'status' => 'error',
                        'message' => "gagal coba lagi dalam {$seconds} detik",
                    ], 429, $headers);
                });
        });
    }
}

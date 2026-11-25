<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
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
        Paginator::useBootstrapFive();

        // Register custom math functions for SQLite connection (used in testing and local SQLite)
        if (DB::getDriverName() === 'sqlite') {
            try {
                $pdo = DB::connection()->getPdo();
                if (method_exists($pdo, 'sqliteCreateFunction')) {
                    $pdo->sqliteCreateFunction('radians', fn($deg) => deg2rad((float)$deg));
                    $pdo->sqliteCreateFunction('degrees', fn($rad) => rad2deg((float)$rad));
                    $pdo->sqliteCreateFunction('cos', fn($rad) => cos((float)$rad));
                    $pdo->sqliteCreateFunction('sin', fn($rad) => sin((float)$rad));
                    $pdo->sqliteCreateFunction('acos', fn($val) => acos(max(-1.0, min(1.0, (float)$val))));
                }
            } catch (\Throwable $e) {
                // Ignore if connection not ready
            }
        }

        // Configure security rate limiters
        RateLimiter::for('otp-verify', function (Request $request) {
            $key = $request->user()?->id ? 'user:'.$request->user()->id : 'ip:'.$request->ip();
            return Limit::perMinute(5)->by($key)->response(function (Request $request, array $headers) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many verification attempts. Please wait 60 seconds before trying again.'
                    ], 429, $headers);
                }
                return back()->with('error', 'Too many verification attempts. Please wait 60 seconds before trying again.');
            });
        });

        RateLimiter::for('otp-resend', function (Request $request) {
            $key = $request->user()?->id ? 'user:'.$request->user()->id : 'ip:'.$request->ip();
            return Limit::perMinutes(15, 3)->by($key)->response(function (Request $request, array $headers) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many OTP resend requests. Please wait a few minutes before trying again.'
                    ], 429, $headers);
                }
                return back()->with('error', 'Too many OTP resend requests. Please wait a few minutes before trying again.');
            });
        });

        RateLimiter::for('qr-verify', function (Request $request) {
            $key = $request->user()?->id ? 'user:'.$request->user()->id : 'ip:'.$request->ip();
            return Limit::perMinute(10)->by($key)->response(function (Request $request, array $headers) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many QR code verification attempts. Please wait 60 seconds before trying again.'
                    ], 429, $headers);
                }
                return redirect()->route('dashboard')->with('error', 'Too many QR code verification attempts. Please wait 60 seconds before trying again.');
            });
        });
    }
}

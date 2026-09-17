<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BpReadingController;
use App\Http\Controllers\Api\V1\CurrentProfileController;
use App\Http\Controllers\Api\V1\GlucoseReadingController;
use App\Http\Controllers\Api\V1\HealthDataConsentController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Middleware\EnsureHealthDataConsent;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('auth/magic-link', [AuthController::class, 'magicLink'])
        ->middleware('throttle:magic-link')
        ->name('auth.magic-link');

    Route::post('auth/token', [AuthController::class, 'token'])
        ->middleware('throttle:magic-link')
        ->name('auth.token');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', MeController::class)->name('me');
        Route::get('consent/health-data', [HealthDataConsentController::class, 'show'])->name('consent.show');
        Route::post('consent/health-data', [HealthDataConsentController::class, 'store'])->name('consent.store');

        Route::middleware(EnsureHealthDataConsent::class)->group(function (): void {
            Route::get('home', HomeController::class)->name('home');

            Route::put('profiles/{profile}/current', [CurrentProfileController::class, 'update'])->name('profiles.current');

            Route::post('bp', [BpReadingController::class, 'store'])->name('bp.store');
            Route::put('bp/{bpReading}', [BpReadingController::class, 'update'])->name('bp.update');
            Route::delete('bp/{bpReading}', [BpReadingController::class, 'destroy'])->name('bp.destroy');

            Route::post('glucose', [GlucoseReadingController::class, 'store'])->name('glucose.store');
            Route::put('glucose/{glucoseReading}', [GlucoseReadingController::class, 'update'])->name('glucose.update');
            Route::delete('glucose/{glucoseReading}', [GlucoseReadingController::class, 'destroy'])->name('glucose.destroy');
        });
    });
});

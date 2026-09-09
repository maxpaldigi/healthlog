<?php

use App\Http\Controllers\Settings\HealthProfileController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/health', [HealthProfileController::class, 'edit'])->name('health-profiles.edit');
    Route::post('settings/health', [HealthProfileController::class, 'store'])->name('health-profiles.store');
    Route::patch('settings/health/{profile}', [HealthProfileController::class, 'update'])->name('health-profiles.update');
    Route::delete('settings/health/{profile}', [HealthProfileController::class, 'destroy'])->name('health-profiles.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
});

<?php

use App\Http\Controllers\Care\InviteCaregiverController;
use App\Http\Controllers\Care\RevokeCaregiverController;
use App\Http\Controllers\Settings\HealthProfileController;
use App\Http\Controllers\Settings\LocaleController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::inertia('settings', 'settings/Index')->name('settings.home');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/health', [HealthProfileController::class, 'edit'])->name('health-profiles.edit');
    Route::post('settings/health', [HealthProfileController::class, 'store'])->name('health-profiles.store');
    Route::patch('settings/health/{profile}', [HealthProfileController::class, 'update'])->name('health-profiles.update');
    Route::delete('settings/health/{profile}', [HealthProfileController::class, 'destroy'])->name('health-profiles.destroy');
    Route::post('settings/health/{profile}/invites', [InviteCaregiverController::class, 'store'])->name('health-profiles.invites.store');
    Route::delete('settings/health/{profile}/members/{member}', [RevokeCaregiverController::class, 'destroy'])->name('health-profiles.members.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
    Route::patch('settings/appearance/locale', [LocaleController::class, 'update'])->name('appearance.locale');
});

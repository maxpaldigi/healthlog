<?php

use App\Http\Controllers\AboutDataController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BpReadingController;
use App\Http\Controllers\Care\AcceptCaregiverInviteController;
use App\Http\Controllers\ClinicController;
use App\Http\Controllers\CompleteOnboardingController;
use App\Http\Controllers\Consent\HealthDataConsentController;
use App\Http\Controllers\CurrentProfileController;
use App\Http\Controllers\GlucoseReadingController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ShareLinkController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\TrendsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    if ($request->user()) {
        return app()->call([app(HomeController::class), '__invoke']);
    }

    return Inertia::render('Welcome');
})->name('home');

Route::get('privacy', PrivacyController::class)->name('privacy');
Route::get('terms', TermsController::class)->name('terms');
Route::get('about/data', AboutDataController::class)->name('about.data');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])
        ->middleware('throttle:magic-link')
        ->name('login.store');
    Route::get('login/check-email', [LoginController::class, 'checkEmail'])->name('login.check-email');
    Route::get('login/magic/{token}', MagicLinkController::class)->name('login.magic');

    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])
        ->middleware('throttle:magic-link')
        ->name('register.store');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('consent/health-data', [HealthDataConsentController::class, 'create'])->name('consent.health-data');
    Route::post('consent/health-data', [HealthDataConsentController::class, 'store'])->name('consent.health-data.store');

    Route::redirect('dashboard', '/')->name('dashboard');
    Route::post('onboarding', CompleteOnboardingController::class)->name('onboarding.complete');

    Route::get('history', HistoryController::class)->name('history');
    Route::get('people', PeopleController::class)->name('people');
    Route::get('trends', TrendsController::class)->name('trends');
    Route::get('report', ReportController::class)->name('report');
    Route::get('report.csv', ReportExportController::class)->name('report.export');
    Route::post('share-links', [ShareLinkController::class, 'store'])->name('share-links.store');

    Route::get('clinic', [ClinicController::class, 'index'])->name('clinic.index');
    Route::post('weights', [ClinicController::class, 'storeWeight'])->name('weights.store');
    Route::delete('weights/{weight}', [ClinicController::class, 'destroyWeight'])->name('weights.destroy');
    Route::post('hba1c', [ClinicController::class, 'storeHba1c'])->name('hba1c.store');
    Route::delete('hba1c/{hba1cResult}', [ClinicController::class, 'destroyHba1c'])->name('hba1c.destroy');
    Route::post('medications', [ClinicController::class, 'storeMedication'])->name('medications.store');
    Route::put('medications/{medication}', [ClinicController::class, 'updateMedication'])->name('medications.update');
    Route::delete('medications/{medication}', [ClinicController::class, 'destroyMedication'])->name('medications.destroy');
    Route::get('scan', [ScanController::class, 'create'])->name('scan');
    Route::post('scan', [ScanController::class, 'store'])
        ->middleware('throttle:scan-photo')
        ->name('scan.store');
    Route::put('profiles/{profile}/current', [CurrentProfileController::class, 'update'])->name('profiles.current');

    Route::post('push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');

    Route::get('bp/log', [BpReadingController::class, 'create'])->name('bp.create');
    Route::post('bp', [BpReadingController::class, 'store'])->name('bp.store');
    Route::get('bp/{bpReading}/edit', [BpReadingController::class, 'edit'])->name('bp.edit');
    Route::put('bp/{bpReading}', [BpReadingController::class, 'update'])->name('bp.update');
    Route::delete('bp/{bpReading}', [BpReadingController::class, 'destroy'])->name('bp.destroy');

    Route::get('sugar/log', [GlucoseReadingController::class, 'create'])->name('glucose.create');
    Route::post('sugar', [GlucoseReadingController::class, 'store'])->name('glucose.store');
    Route::get('sugar/{glucoseReading}/edit', [GlucoseReadingController::class, 'edit'])->name('glucose.edit');
    Route::put('sugar/{glucoseReading}', [GlucoseReadingController::class, 'update'])->name('glucose.update');
    Route::delete('sugar/{glucoseReading}', [GlucoseReadingController::class, 'destroy'])->name('glucose.destroy');
});

Route::get('shared/{token}', [ShareLinkController::class, 'show'])->name('shared.show');
Route::get('invites/{member}', AcceptCaregiverInviteController::class)
    ->middleware('signed')
    ->name('invites.accept');

require __DIR__.'/settings.php';

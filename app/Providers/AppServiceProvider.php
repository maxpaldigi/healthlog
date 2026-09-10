<?php

namespace App\Providers;

use Anthropic\Client;
use App\Actions\Food\IdentifyFoodFromGemini;
use App\Actions\Readings\ExtractReadingFromGemini;
use App\Actions\Readings\ExtractReadingFromImage;
use App\Contracts\ExtractsDeviceReading;
use App\Contracts\ExtractsFoodFromPhoto;
use App\Contracts\SendsWebPush;
use App\Support\RecordingWebPush;
use App\Support\SendWebPush;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, fn (): Client => new Client(
            apiKey: (string) config('services.anthropic.key'),
        ));

        $this->app->bind(ExtractsDeviceReading::class, function (): ExtractsDeviceReading {
            $geminiKey = config('services.gemini.key')
                ?: ($_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY'));

            if (filled($geminiKey) || blank(config('services.anthropic.key'))) {
                return $this->app->make(ExtractReadingFromGemini::class);
            }

            return $this->app->make(ExtractReadingFromImage::class);
        });

        $this->app->bind(ExtractsFoodFromPhoto::class, IdentifyFoodFromGemini::class);

        $this->app->singleton(SendsWebPush::class, function (): SendsWebPush {
            if ($this->app->environment('testing')) {
                return new RecordingWebPush;
            }

            return $this->app->make(SendWebPush::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->useUtcDatabaseSession();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        $this->useLogMailerWhenRemoteMailIsNotReady();

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    private function useLogMailerWhenRemoteMailIsNotReady(): void
    {
        if (config('mail.default') === 'resend' && blank(config('services.resend.key'))) {
            config(['mail.default' => 'log']);

            return;
        }

        $smtpHost = (string) config('mail.mailers.smtp.host');

        if (config('mail.default') === 'smtp' && in_array($smtpHost, ['127.0.0.1', 'localhost'], true)) {
            config(['mail.default' => 'log']);
        }
    }

    private function useUtcDatabaseSession(): void
    {
        try {
            if (DB::getDriverName() !== 'pgsql') {
                return;
            }

            DB::statement("SET TIME ZONE 'UTC'");
        } catch (\Throwable) {
            // Package discovery and first-run artisan may not have a database yet.
        }
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('scan-photo', function (Request $request) {
            return Limit::perMinute(10)->by((string) ($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });

        RateLimiter::for('food-lookup', function (Request $request) {
            return Limit::perMinute(30)->by((string) ($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });

        RateLimiter::for('rxnorm-lookup', function (Request $request) {
            return Limit::perMinute(30)->by((string) ($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });

        RateLimiter::for('share-respond', function (Request $request) {
            return Limit::perMinute(10)->by((string) $request->ip());
        });
    }
}

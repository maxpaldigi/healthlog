<?php

namespace App\Providers;

use Anthropic\Client;
use App\Actions\Readings\ExtractReadingFromImage;
use App\Contracts\ExtractsDeviceReading;
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

        $this->app->bind(ExtractsDeviceReading::class, ExtractReadingFromImage::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
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

    private function configureRateLimiting(): void
    {
        RateLimiter::for('scan-photo', function (Request $request) {
            return Limit::perMinute(10)->by((string) ($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });
    }
}

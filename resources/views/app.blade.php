<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark', 'large-text' => $largeText ?? false])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                @if (app()->isLocal())
                try {
                    if ('serviceWorker' in navigator) {
                        navigator.serviceWorker.getRegistrations().then(function (registrations) {
                            registrations.forEach(function (registration) {
                                registration.unregister();
                            });
                        });
                    }
                    if (window.caches) {
                        caches.keys().then(function (keys) {
                            keys.forEach(function (key) {
                                caches.delete(key);
                            });
                        });
                    }
                } catch (error) {}
                @endif
                try {
                    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                    if (timezone) {
                        document.cookie = 'system_timezone=' + encodeURIComponent(timezone) + '; path=/; max-age=31536000; samesite=lax';
                    }
                } catch (error) {}

                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }

                try {
                    if (localStorage.getItem('large_text') === '1') {
                        document.documentElement.classList.add('large-text');
                    }
                } catch (error) {}
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #f6f0e8;
            }

            html.dark {
                background-color: #1a1614;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/logo.png" type="image/png" sizes="150x150">
        <link rel="icon" href="/favicon-32.png" type="image/png" sizes="32x32">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#2A3B8F">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="HealthyMe">

        @php
            $shareTitle = config('app.name', 'HealthyMe');
            $shareDescription = 'Blood pressure and sugar, easy to keep. Photograph the meter or type the numbers.';
            $shareImage = url('/og.jpg');
            $shareUrl = url()->current();
        @endphp
        <meta name="description" content="{{ $shareDescription }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $shareTitle }}">
        <meta property="og:title" content="{{ $shareTitle }}">
        <meta property="og:description" content="{{ $shareDescription }}">
        <meta property="og:url" content="{{ $shareUrl }}">
        <meta property="og:image" content="{{ $shareImage }}">
        <meta property="og:image:type" content="image/jpeg">
        <meta property="og:image:width" content="1280">
        <meta property="og:image:height" content="720">
        <meta property="og:image:alt" content="HealthyMe — blood pressure and sugar log">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $shareTitle }}">
        <meta name="twitter:description" content="{{ $shareDescription }}">
        <meta name="twitter:image" content="{{ $shareImage }}">

        @php
            $analyticsId = filled(config('services.google_analytics.measurement_id'))
                && app()->isProduction()
                ? (string) config('services.google_analytics.measurement_id')
                : null;
        @endphp
        @if ($analyticsId)
            {{-- Google Analytics 4. Consent Mode starts denied; cookies only after Accept. --}}
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('consent', 'default', {
                    analytics_storage: 'denied',
                    ad_storage: 'denied',
                    ad_user_data: 'denied',
                    ad_personalization: 'denied',
                    wait_for_update: 500
                });
                gtag('js', new Date());
                gtag('config', @json($analyticsId), { anonymize_ip: true, send_page_view: true });
                try {
                    if (localStorage.getItem('healthyme.analytics') === '1') {
                        gtag('consent', 'update', { analytics_storage: 'granted' });
                    }
                } catch (error) {}
            </script>
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $analyticsId }}"></script>
        @endif

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'HealthyMe') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
        @if ($analyticsId)
            <div
                id="analytics-consent"
                hidden
                class="fixed inset-x-0 bottom-0 z-50 border-t border-[#d6cfc4] bg-[#f6f0e8] p-4 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-[0_-8px_24px_rgba(26,22,20,0.12)]"
                role="dialog"
                aria-labelledby="analytics-consent-title"
                aria-describedby="analytics-consent-copy"
            >
                <div class="mx-auto flex w-full max-w-md flex-col gap-3">
                    <p id="analytics-consent-title" class="text-lg font-bold text-[#1a1614]">
                        Visit counting
                    </p>
                    <p id="analytics-consent-copy" class="text-base leading-relaxed text-[#4a433c]">
                        We can use Google Analytics to count page visits. No blood pressure, sugar, or other health readings are sent. You can say no.
                    </p>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button
                            type="button"
                            id="analytics-consent-accept"
                            class="flex min-h-14 flex-1 cursor-pointer items-center justify-center rounded-full bg-[#2A3B8F] px-4 text-lg font-bold text-white"
                        >
                            OK, count visits
                        </button>
                        <button
                            type="button"
                            id="analytics-consent-refuse"
                            class="flex min-h-14 flex-1 cursor-pointer items-center justify-center rounded-full border border-[#d6cfc4] bg-white px-4 text-lg font-bold text-[#1a1614]"
                        >
                            No thanks
                        </button>
                    </div>
                </div>
            </div>
            <script>
                (function () {
                    var key = 'healthyme.analytics';
                    var banner = document.getElementById('analytics-consent');
                    if (!banner) {
                        return;
                    }

                    function choice() {
                        try {
                            return localStorage.getItem(key);
                        } catch (error) {
                            return '0';
                        }
                    }

                    function remember(value) {
                        try {
                            localStorage.setItem(key, value);
                        } catch (error) {}
                    }

                    function hide() {
                        banner.hidden = true;
                    }

                    if (choice() === null) {
                        banner.hidden = false;
                    }

                    document.getElementById('analytics-consent-accept')?.addEventListener('click', function () {
                        remember('1');
                        if (typeof gtag === 'function') {
                            gtag('consent', 'update', { analytics_storage: 'granted' });
                        }
                        hide();
                    });

                    document.getElementById('analytics-consent-refuse')?.addEventListener('click', function () {
                        remember('0');
                        if (typeof gtag === 'function') {
                            gtag('consent', 'update', { analytics_storage: 'denied' });
                        }
                        hide();
                    });
                })();
            </script>
        @endif
    </body>
</html>

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

        @include('partials.cookie-consent-head')

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'HealthyMe') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
        @include('partials.cookie-consent')
    </body>
</html>

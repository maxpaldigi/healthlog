import { createInertiaApp, router } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import { initializeLargeText } from '@/composables/useLargeText';
import { initializeReadAloud } from '@/composables/useReadAloud';
import { rememberSystemTimezone } from '@/lib/systemClock';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import LoggingLayout from '@/layouts/LoggingLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { initializeOfflineQueue } from '@/lib/offlineQueue';
import { registerServiceWorker } from '@/lib/registerServiceWorker';

const appName = import.meta.env.VITE_APP_NAME || 'HealthyMe';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome' || name.startsWith('legal/'):
                return null;
            case name === 'Home' ||
                name === 'People' ||
                name.startsWith('readings/') ||
                name.startsWith('food/') ||
                name.startsWith('clinic/'):
                return LoggingLayout;
            case name.startsWith('shared/'):
                return null;
            case name.startsWith('admin/'):
                return SuperAdminLayout;
            case name.startsWith('auth/') || name.startsWith('consent/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [LoggingLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#2A3B8F',
    },
});

// This will set light / dark mode and large text on page load...
initializeTheme();
initializeLargeText();
initializeReadAloud();

const systemTimezone = rememberSystemTimezone();

if (systemTimezone !== '') {
    router.on('before', (event) => {
        event.detail.visit.headers = {
            ...event.detail.visit.headers,
            'X-Timezone': systemTimezone,
        };
    });
}

// This will listen for flash toast data from the server...
initializeFlashToast();

initializeOfflineQueue();

registerServiceWorker();

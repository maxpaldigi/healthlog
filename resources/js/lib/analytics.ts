import { router } from '@inertiajs/vue3';

declare global {
    interface Window {
        gtag?: (...args: unknown[]) => void;
        dataLayer?: unknown[];
    }
}

/**
 * Send a page view on Inertia navigations when Google Analytics is loaded
 * and the person has accepted analytics cookies.
 */
export function initializeAnalytics(): void {
    if (typeof window.gtag !== 'function') {
        return;
    }

    router.on('navigate', (event) => {
        try {
            const raw = window.localStorage.getItem('healthyme.cookie_consent');
            const legacy = window.localStorage.getItem('healthyme.analytics');
            const accepted =
                (raw !== null && JSON.parse(raw).analytics === true) ||
                legacy === '1';

            if (!accepted) {
                return;
            }
        } catch {
            return;
        }

        window.gtag?.('event', 'page_view', {
            page_path: event.detail.page.url,
            page_title: document.title,
        });
    });
}

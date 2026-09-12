export function registerServiceWorker(): void {
    if (typeof window === 'undefined' || !('serviceWorker' in navigator)) {
        return;
    }

    if (import.meta.env.DEV) {
        void navigator.serviceWorker.getRegistrations().then((registrations) => {
            registrations.forEach((registration) => {
                void registration.unregister();
            });
        });

        return;
    }

    window.addEventListener('load', () => {
        void navigator.serviceWorker
            .register('/sw.js', { updateViaCache: 'none' })
            .then((registration) => {
                const check = (): void => {
                    void registration.update();
                };

                check();
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        check();
                    }
                });
            })
            .catch(() => {
                // Install still works without a worker.
            });
    });
}

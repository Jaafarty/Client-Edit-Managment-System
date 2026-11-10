import { createInertiaApp } from '@inertiajs/react';

const appName =
    import.meta.env.VITE_APP_NAME || 'Client Edit Management System';

if (typeof window !== 'undefined') {
    function reloadHistoryPage() {
        document.documentElement.hidden = true;
        window.location.reload();
    }

    window.addEventListener(
        'popstate',
        (event) => {
            const state: unknown = event.state;
            if (state && typeof state === 'object' && 'page' in state) {
                event.stopImmediatePropagation();
                reloadHistoryPage();
            }
        },
        { capture: true },
    );

    window.addEventListener('pagehide', (event) => {
        if (event.persisted) {
            document.documentElement.hidden = true;
        }
    });

    window.addEventListener(
        'pageshow',
        (event) => {
            if (event.persisted) {
                event.stopImmediatePropagation();
                reloadHistoryPage();
            }
        },
        { capture: true },
    );
}

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    strictMode: true,
});

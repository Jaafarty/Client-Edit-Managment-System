import { createInertiaApp } from '@inertiajs/react';

const appName =
    import.meta.env.VITE_APP_NAME || 'Client Edit Management System';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    strictMode: true,
});

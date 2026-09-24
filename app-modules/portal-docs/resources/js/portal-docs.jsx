import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

createInertiaApp({
    pages: './pages',
    progress: { color: 'var(--color-primary)' },
    defaults: { prefetch: { cacheFor: '5m' } },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});

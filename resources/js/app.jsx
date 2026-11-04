import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { initTelegram } from './telegram/webapp';
import { setI18n } from './lib/i18n';

const appName = import.meta.env.VITE_APP_NAME || 'e-notebook';

initTelegram();

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        return pages[`./Pages/${name}.jsx`];
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <App {...props}>
                {({ Component, key, props: pageProps }) => {
                    // Sahifa chizilishidan oldin tilni o'rnatamiz.
                    setI18n(pageProps);
                    return <Component key={key} {...pageProps} />;
                }}
            </App>,
        );
    },
    progress: { color: '#1F8550' },
});

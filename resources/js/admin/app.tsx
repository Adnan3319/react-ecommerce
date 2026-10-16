import { createInertiaApp } from '@inertiajs/react';
import type { ResolvedComponent } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ReactNode } from 'react';
import { createRoot } from 'react-dom/client';
import AdminShell from './AdminShell';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pagePrefix = 'Admin/';
const authPagePrefix = 'Admin/AuthPages/';

const adminLayout = (page: ReactNode) => <AdminShell>{page}</AdminShell>;
const adminAuthLayout = (page: ReactNode) => (
    <AdminShell withLayout={false}>{page}</AdminShell>
);

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        const pages = import.meta.glob<{ default: ResolvedComponent }>(
            './pages/**/*.tsx',
        );
        const page = await resolvePageComponent(
            `./pages/${name.slice(pagePrefix.length)}.tsx`,
            pages,
        );

        page.default.layout ??= name.startsWith(authPagePrefix)
            ? adminAuthLayout
            : adminLayout;

        return page.default;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});

import { Link as InertiaLink, usePage } from '@inertiajs/react';
import type { ComponentProps } from 'react';

const ADMIN_PREFIX = '/admin';

type LinkProps = Omit<ComponentProps<typeof InertiaLink>, 'href'> & {
    to: string;
};

function toAdminUrl(to: string) {
    if (!to.startsWith('/') || to.startsWith(ADMIN_PREFIX)) return to;
    if (to === '/') return `${ADMIN_PREFIX}/dashboard`;
    return `${ADMIN_PREFIX}${to}`;
}

export function Link({ to, ...rest }: LinkProps) {
    return <InertiaLink href={toAdminUrl(to)} {...rest} />;
}

export function useLocation() {
    const { url } = usePage();
    const [rawPath, search = ''] = url.split('?');

    let pathname = rawPath.startsWith(ADMIN_PREFIX)
        ? rawPath.slice(ADMIN_PREFIX.length)
        : rawPath;

    if (pathname === '' || pathname === '/dashboard') pathname = '/';

    return {
        pathname,
        search: search ? `?${search}` : '',
        hash: '',
    };
}

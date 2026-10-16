import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/shared/lib';
import { parseSearchParams } from '@/storefront/features/search';
import { MAIN_NAV_ITEMS, isActivePath, type MainNavItem } from '../navigation';

function isItemActive(
    item: MainNavItem,
    url: string,
    category: string | null,
): boolean {
    if (item.categorySlug !== null) {
        return category === item.categorySlug;
    }

    return isActivePath(url, item.href);
}

export const MAIN_NAV_ID = 'primary-navigation';

type MainNavProps = {
    /** Mobile only: whether the collapsed menu is expanded. */
    isOpen: boolean;
    onNavigate: () => void;
};

const LINK_BASE =
    'relative flex items-center px-5 py-3.5 text-sm font-semibold tracking-wide whitespace-nowrap uppercase transition-colors md:h-12 md:px-4 md:py-0';

export default function MainNav({ isOpen, onNavigate }: MainNavProps) {
    const { url } = usePage();
    const { category } = parseSearchParams(url);

    return (
        <nav
            id={MAIN_NAV_ID}
            aria-label="Primary"
            className={cn('bg-ink md:block', isOpen ? 'block' : 'hidden')}
        >
            <ul className="mx-auto flex max-w-7xl flex-col divide-y divide-white/10 md:flex-row md:justify-center md:gap-1 md:divide-y-0 md:px-4">
                {MAIN_NAV_ITEMS.map((item) => {
                    const isActive = isItemActive(item, url, category);

                    return (
                        <li key={item.label} className="group relative">
                            <Link
                                href={item.href}
                                onClick={onNavigate}
                                aria-current={isActive ? 'page' : undefined}
                                className={cn(
                                    LINK_BASE,
                                    isActive
                                        ? 'bg-white/5 text-white md:bg-transparent'
                                        : 'text-white/70 hover:text-white',
                                )}
                            >
                                {item.label}
                                <span
                                    aria-hidden="true"
                                    className={cn(
                                        'absolute bg-accent-500 transition-transform',
                                        'inset-y-0 left-0 w-1 origin-top md:inset-x-4 md:inset-y-auto md:bottom-0 md:h-0.5 md:w-auto md:origin-left md:rounded-full',
                                        isActive
                                            ? 'scale-100'
                                            : 'scale-0 md:group-hover:scale-100',
                                    )}
                                />
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}

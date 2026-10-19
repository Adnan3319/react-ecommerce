import type { IconName } from '@/storefront/components/ui';
import { about, cart, contact, home, search } from '@/routes';
import type { RouteDefinition } from '@/wayfinder';
import { CATEGORIES } from '@/storefront/config/site';
import { categoryHref } from '@/storefront/lib/category-href';

type Href = RouteDefinition<'get'>;

export type MobileNavItem = {
    label: string;
    icon: IconName;
    href: Href;
};

export type MainNavItem = {
    label: string;
    href: Href;
    /** Set for category links; marks the item active on that category. */
    categorySlug: string | null;
};

export const MOBILE_NAV_ITEMS: MobileNavItem[] = [
    { label: 'Home', icon: 'home', href: home() },
    { label: 'Search', icon: 'search', href: search() },
    { label: 'Cart', icon: 'cart', href: cart() },
];

export const CATEGORY_LINKS = CATEGORIES.map((category) => ({
    ...category,
    href: categoryHref(category.slug),
}));

export const MAIN_NAV_ITEMS: MainNavItem[] = [
    { label: 'Home', href: home(), categorySlug: null },
    ...CATEGORIES.map((category) => ({
        label: category.label,
        href: categoryHref(category.slug),
        categorySlug: category.slug,
    })),
    { label: 'About Us', href: about(), categorySlug: null },
    { label: 'Contact', href: contact(), categorySlug: null },
];

/** Compares the current Inertia URL with a route, ignoring the query string. */
export function isActivePath(currentUrl: string, href: Href): boolean {
    return currentUrl.split('?')[0] === href.url.split('?')[0];
}

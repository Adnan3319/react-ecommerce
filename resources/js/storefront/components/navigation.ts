import type { IconName } from '@/storefront/components/ui';
import { cart, contact, home, search } from '@/routes';
import { show as showPage } from '@/routes/pages';
import type { RouteDefinition } from '@/wayfinder';
import { ABOUT_PAGE_SLUG, type Category } from '@/storefront/config/site';
import { categoryHref } from '@/storefront/lib/category-href';

type Href = RouteDefinition<'get'>;

export type MobileNavItem = {
    label: string;
    href: Href;
    icon: IconName;
};

export type MainNavItem = {
    label: string;
    href: Href;
    categorySlug?: string | null;
};

export function mainNavItems(_categories: Category[]): MainNavItem[] {
    return [
        { label: 'Home', href: home(), categorySlug: null },
        {
            label: 'Kitchenware',
            href: search({ query: { category: 'kitchenware' } }),
            categorySlug: 'kitchenware',
        },
        {
            label: 'Fashion',
            href: search({ query: { category: 'fashion' } }),
            categorySlug: 'fashion',
        },
        {
            label: 'About Us',
            href: showPage(ABOUT_PAGE_SLUG),
            categorySlug: null,
        },
        { label: 'Contact', href: contact(), categorySlug: null },
    ];
}

export const MOBILE_NAV_ITEMS: MobileNavItem[] = [
    { label: 'Home', href: home(), icon: 'home' },
    { label: 'Search', href: search(), icon: 'search' },
    { label: 'Cart', href: cart(), icon: 'cart' },
    { label: 'About', href: showPage(ABOUT_PAGE_SLUG), icon: 'info' },
];

export function categoryLinks(categories: Category[]) {
    return categories.map((category) => ({
        label: category.label,
        slug: category.slug,
        href: categoryHref(category.slug),
    }));
}

export function isActivePath(url: string, href: Href): boolean {
    const cleanUrl = url.split('?')[0];
    const cleanHref = href.url.split('?')[0];
    return cleanUrl === cleanHref;
}

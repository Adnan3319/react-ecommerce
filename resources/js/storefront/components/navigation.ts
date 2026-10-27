import type { IconName } from '@/storefront/components/ui';
import { cart, contact, home, search } from '@/routes';
import type { RouteDefinition } from '@/wayfinder';
import type { Category } from '@/storefront/config/site';
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
        { label: 'Kitchenware', href: search(), categorySlug: 'kitchenware' },
        { label: 'Fashion', href: search(), categorySlug: 'fashion' },
        { label: 'About Us', href: route('pages.show', 'about') as unknown as Href, categorySlug: null },
        { label: 'Contact', href: contact(), categorySlug: null },
    ];
}

export const MOBILE_NAV_ITEMS: MobileNavItem[] = [
    { label: 'Home', href: home(), icon: 'home' },
    { label: 'Search', href: search(), icon: 'search' },
    { label: 'Cart', href: cart(), icon: 'cart' },
    { label: 'About', href: route('pages.show', 'about') as unknown as Href, icon: 'user' },
];

export function categoryLinks(categories: Category[]) {
    return categories.map((category) => ({
        label: category.name,
        slug: category.slug,
        href: categoryHref(category),
    }));
}

export function isActivePath(url: string, href: string): boolean {
    const cleanUrl = url.split('?')[0];
    const cleanHref = href.split('?')[0];
    return cleanUrl === cleanHref;
}
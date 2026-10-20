import { usePage } from '@inertiajs/react';
import type { Category } from '@/storefront/config/site';

/** Active categories shared by the backend on every storefront page. */
export function useCategories(): Category[] {
    return usePage<{ categories: Category[] }>().props.categories;
}

export function useCategory(slug: string | null | undefined): Category | undefined {
    const categories = useCategories();

    return slug ? categories.find((category) => category.slug === slug) : undefined;
}

import { usePage } from '@inertiajs/react';
import type { Category, CategoryNode } from '@/storefront/config/site';

/** Active top-level categories, each with its active sub-categories under `children`. */
export function useCategories(): CategoryNode[] {
    return usePage<{ categories: CategoryNode[] }>().props.categories;
}

/** Finds a top-level category or a sub-category by slug. */
export function useCategory(
    slug: string | null | undefined,
): Category | undefined {
    const categories = useCategories();

    if (!slug) {
        return undefined;
    }

    return categories
        .flatMap((category) => [category, ...category.children])
        .find((category) => category.slug === slug);
}

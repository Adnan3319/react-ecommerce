import { search } from '@/routes';
import type { ProductDetail } from '@/storefront/features/product';
import type { RouteDefinition } from '@/wayfinder';

export const SORT_OPTIONS = [
    { value: 'default', label: 'Featured' },
    { value: 'price-asc', label: 'Price: Low to High' },
    { value: 'price-desc', label: 'Price: High to Low' },
    { value: 'name', label: 'Name: A to Z' },
] as const;

export type SortKey = (typeof SORT_OPTIONS)[number]['value'];

export type SearchParams = {
    q: string;
    category: string | null;
    sort: SortKey;
};

const DEFAULT_SORT: SortKey = 'default';

function isSortKey(value: string | null): value is SortKey {
    return SORT_OPTIONS.some((option) => option.value === value);
}

/** Reads search params from an Inertia page URL (SSR-safe, no window). */
export function parseSearchParams(url: string): SearchParams {
    const params = new URLSearchParams(url.split('?')[1] ?? '');
    const sort = params.get('sort');

    return {
        q: (params.get('q') ?? '').trim(),
        category: params.get('category') || null,
        sort: isSortKey(sort) ? sort : DEFAULT_SORT,
    };
}

export function buildSearchHref(params: SearchParams): RouteDefinition<'get'> {
    const query: Record<string, string> = {};

    if (params.q) {
        query.q = params.q;
    }

    if (params.category) {
        query.category = params.category;
    }

    if (params.sort !== DEFAULT_SORT) {
        query.sort = params.sort;
    }

    return search({ query });
}

export function filterProducts(
    products: ProductDetail[],
    { q, category, sort }: SearchParams,
): ProductDetail[] {
    const needle = q.toLowerCase();

    const matches = products.filter((product) => {
        if (category && product.categorySlug !== category) {
            return false;
        }

        return (
            needle === '' ||
            product.name.toLowerCase().includes(needle) ||
            product.description.toLowerCase().includes(needle)
        );
    });

    switch (sort) {
        case 'price-asc':
            return [...matches].sort((a, b) => a.price - b.price);
        case 'price-desc':
            return [...matches].sort((a, b) => b.price - a.price);
        case 'name':
            return [...matches].sort((a, b) => a.name.localeCompare(b.name));
        default:
            return matches;
    }
}

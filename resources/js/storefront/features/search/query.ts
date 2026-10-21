import { search } from '@/routes';
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

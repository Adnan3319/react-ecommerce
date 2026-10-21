import { router } from '@inertiajs/react';
import type { RequestPayload } from '@inertiajs/core';
import { search } from '@/routes';
import { show } from '@/routes/categories';
import { LISTING_PROPS, defaultSort } from './constants';
import type { ListingFilters, ListingMode, SortValue } from './types';

type Active = ListingFilters['active'];

/** Query string of the listing for the given filters (the page number is dropped on purpose). */
function toQuery(active: Active, mode: ListingMode): RequestPayload {
    const query: RequestPayload = {};

    if (mode === 'search') {
        if (active.q) {
            query.q = active.q;
        }

        if (active.category) {
            query.category = active.category;
        }
    }

    if (active.min) {
        query.min = active.min;
    }

    if (active.max) {
        query.max = active.max;
    }

    if (active.in_stock) {
        query.in_stock = 1;
    }

    if (active.sort !== defaultSort(active)) {
        query.sort = active.sort;
    }

    const heads = Object.entries(active.f).filter(
        ([, values]) => values.length > 0,
    );

    if (heads.length > 0) {
        query.f = Object.fromEntries(
            heads.map(([head, values]) => [head, values.join(',')]),
        );
    }

    return query;
}

/** URL of the listing for a mode, used for filter visits and for links to other categories. */
export function listingUrl(mode: ListingMode, categorySlug: string | null) {
    return mode === 'category' && categorySlug
        ? show(categorySlug).url
        : search().url;
}

/**
 * Filter state lives in the URL: every change is a partial Inertia visit to the current listing
 * route, so the page is shareable and the back button works.
 */
export function useCatalogFilters(
    mode: ListingMode,
    categorySlug: string | null,
    active: Active,
) {
    const url = listingUrl(mode, categorySlug);

    function visit(next: Active): void {
        router.get(url, toQuery(next, mode), {
            preserveScroll: true,
            preserveState: true,
            only: LISTING_PROPS,
        });
    }

    function toggleValue(head: string, value: string): void {
        const current = active.f[head] ?? [];
        const values = current.includes(value)
            ? current.filter((item) => item !== value)
            : [...current, value];

        visit({ ...active, f: { ...active.f, [head]: values } });
    }

    return {
        setPrice: (min: string, max: string) =>
            visit({ ...active, min: min || null, max: max || null }),
        setInStock: (inStock: boolean) =>
            visit({ ...active, in_stock: inStock }),
        setSort: (sort: SortValue) => visit({ ...active, sort }),
        toggleValue,
        clearAll: () =>
            visit({
                ...active,
                min: null,
                max: null,
                in_stock: false,
                sort: defaultSort(active),
                f: {},
            }),
    };
}

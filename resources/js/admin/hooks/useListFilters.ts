import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { PER_PAGE_OPTIONS } from '@admin/components/DataTable';
import {
    STATUS_ACTIVE,
    STATUS_FILTER_ALL,
    STATUS_INACTIVE,
} from '@/shared/constants/status';
import type { ActiveStatus } from '@/shared/types';

const SEARCH_DEBOUNCE_MS = 300;

/** The `filters` prop every admin list page receives (see IndexListRequest). */
export type ListFilters = {
    search: string | null;
    status: ActiveStatus | null;
    per_page: number;
};

type Query = {
    search?: string;
    status?: ActiveStatus;
    per_page?: number;
    page?: number;
};

function visit(url: string, query: Query, setProcessing: (processing: boolean) => void): void {
    router.get(url, query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => setProcessing(true),
        onFinish: () => setProcessing(false),
    });
}

function toStatus(value: string): ActiveStatus | undefined {
    return value === STATUS_ACTIVE || value === STATUS_INACTIVE ? value : undefined;
}

/**
 * Keeps search, status filter and rows-per-page of an admin list in the URL
 * (router.get), so the list is shareable, survives reloads and works with the
 * browser back button. `url` is the list's index route.
 */
export function useListFilters(url: string, filters: ListFilters) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [processing, setProcessing] = useState(false);

    const appliedSearch = filters.search ?? '';
    const appliedStatus = filters.status ?? undefined;
    // The default size stays out of the URL.
    const appliedPerPage = filters.per_page === PER_PAGE_OPTIONS[0] ? undefined : filters.per_page;

    useEffect(() => {
        if (search === appliedSearch) {
            return;
        }

        const timer = setTimeout(() => {
            visit(
                url,
                { search: search.trim() || undefined, status: appliedStatus, per_page: appliedPerPage },
                setProcessing,
            );
        }, SEARCH_DEBOUNCE_MS);

        return () => clearTimeout(timer);
    }, [url, search, appliedSearch, appliedStatus, appliedPerPage]);

    const setStatus = (value: string) =>
        visit(
            url,
            { search: appliedSearch || undefined, status: toStatus(value), per_page: appliedPerPage },
            setProcessing,
        );

    const setPerPage = (perPage: number) =>
        visit(
            url,
            {
                search: appliedSearch || undefined,
                status: appliedStatus,
                per_page: perPage === PER_PAGE_OPTIONS[0] ? undefined : perPage,
            },
            setProcessing,
        );

    const goToPage = (page: number) =>
        visit(
            url,
            { search: appliedSearch || undefined, status: appliedStatus, per_page: appliedPerPage, page },
            setProcessing,
        );

    return {
        search,
        setSearch,
        status: appliedStatus ?? STATUS_FILTER_ALL,
        setStatus,
        perPage: filters.per_page,
        setPerPage,
        goToPage,
        processing,
    };
}

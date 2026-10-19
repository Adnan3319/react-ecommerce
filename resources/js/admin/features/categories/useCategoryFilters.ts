import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { index } from '@/routes/admin/categories';
import { STATUS_ACTIVE, STATUS_INACTIVE } from '@/shared/constants/status';
import type { ActiveStatus } from '@/shared/types';
import { SEARCH_DEBOUNCE_MS, STATUS_FILTER_ALL } from './constants';
import type { CategoryFilters } from './types';

type Query = {
    search?: string;
    status?: ActiveStatus;
    page?: number;
};

function visit(query: Query, setProcessing: (processing: boolean) => void): void {
    router.get(index().url, query, {
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
 * Keeps search and status filter in the URL (router.get), so the list is
 * shareable, survives reloads and works with the browser back button.
 */
export function useCategoryFilters(filters: CategoryFilters) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [processing, setProcessing] = useState(false);

    const appliedSearch = filters.search ?? '';
    const appliedStatus = filters.status ?? undefined;

    useEffect(() => {
        if (search === appliedSearch) {
            return;
        }

        const timer = setTimeout(() => {
            visit({ search: search.trim() || undefined, status: appliedStatus }, setProcessing);
        }, SEARCH_DEBOUNCE_MS);

        return () => clearTimeout(timer);
    }, [search, appliedSearch, appliedStatus]);

    const setStatus = (value: string) =>
        visit({ search: appliedSearch || undefined, status: toStatus(value) }, setProcessing);

    const goToPage = (page: number) =>
        visit({ search: appliedSearch || undefined, status: appliedStatus, page }, setProcessing);

    return {
        search,
        setSearch,
        status: appliedStatus ?? STATUS_FILTER_ALL,
        setStatus,
        goToPage,
        processing,
    };
}

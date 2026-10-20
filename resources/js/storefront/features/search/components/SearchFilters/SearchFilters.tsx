import { Link, router } from '@inertiajs/react';
import { cn } from '@/shared/lib';
import { useCategories } from '@/storefront/lib/useCategories';
import { SORT_OPTIONS, buildSearchHref } from '../../query';
import type { SearchParams, SortKey } from '../../query';

type SearchFiltersProps = {
    params: SearchParams;
};

const CHIP_BASE =
    'inline-flex h-9 items-center rounded-full border px-4 text-sm font-medium whitespace-nowrap transition-colors';

export default function SearchFilters({ params }: SearchFiltersProps) {
    const categories = useCategories();
    const chips = [
        { slug: null, label: 'All' },
        ...categories.map(({ slug, label }) => ({ slug, label })),
    ];

    function handleSortChange(sort: SortKey) {
        router.get(
            buildSearchHref({ ...params, sort }).url,
            {},
            { preserveScroll: true },
        );
    }

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <nav aria-label="Filter by category">
                <ul className="flex gap-2 overflow-x-auto pb-1">
                    {chips.map((chip) => {
                        const isActive = params.category === chip.slug;

                        return (
                            <li key={chip.label} className="shrink-0">
                                <Link
                                    href={buildSearchHref({
                                        ...params,
                                        category: chip.slug,
                                    })}
                                    aria-current={isActive ? 'true' : undefined}
                                    className={cn(
                                        CHIP_BASE,
                                        isActive
                                            ? 'border-transparent btn-accent'
                                            : 'border-neutral-300 bg-white text-neutral-700 hover:border-accent-600 hover:text-accent-600',
                                    )}
                                >
                                    {chip.label}
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            <label className="flex items-center gap-2 text-sm text-neutral-600">
                <span className="shrink-0">Sort by</span>
                <select
                    value={params.sort}
                    onChange={(event) =>
                        handleSortChange(event.target.value as SortKey)
                    }
                    className="h-10 min-w-0 flex-1 rounded-control border border-neutral-300 bg-white px-3 text-sm font-medium text-neutral-900 outline-none focus:border-accent-600 focus:ring-2 focus:ring-accent-100 sm:flex-none"
                >
                    {SORT_OPTIONS.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            </label>
        </div>
    );
}

import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import { useCategory } from '@/storefront/lib/useCategories';
import type { SearchParams } from '../../query';

type SearchResultsProps = {
    params: SearchParams;
};

function resolveHeading(q: string, categoryLabel: string | undefined): string {
    if (q) {
        return `Results for "${q}"`;
    }

    return categoryLabel || 'All Products';
}

/** Results come from the backend in the search step; until then the page shows its empty state. */
export default function SearchResults({ params }: SearchResultsProps) {
    const category = useCategory(params.category);

    return (
        <section aria-live="polite">
            <header className="mb-5 flex flex-wrap items-end justify-between gap-2">
                <h1 className="text-2xl font-bold tracking-tight break-words text-neutral-900 sm:text-3xl">
                    {resolveHeading(params.q, category?.label)}
                </h1>
            </header>

            <div className="rounded-card border border-dashed border-neutral-300 bg-cream-50 px-6 py-16 text-center">
                <p className="text-lg font-semibold text-neutral-900">
                    No products found
                </p>
                <p className="mt-1 text-sm text-neutral-600">
                    Try a different keyword or clear the filters.
                </p>
                <Link
                    href={home()}
                    className="mt-5 inline-flex h-11 items-center rounded-control btn-accent px-5 text-sm font-semibold text-white"
                >
                    Back to home
                </Link>
            </div>
        </section>
    );
}

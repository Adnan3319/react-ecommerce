import { router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { search } from '@/routes';
import { Icon } from '@/storefront/components/ui';

type ListingSearchBoxProps = {
    q: string | null;
    /** Slug of the category the search stays inside. */
    category: string | null;
};

/** Search field of the listing page; searching keeps the current category. */
export default function ListingSearchBox({
    q,
    category,
}: ListingSearchBoxProps) {
    function handleSubmit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        const value = new FormData(event.currentTarget).get('q');
        const term = typeof value === 'string' ? value.trim() : '';

        router.get(search().url, {
            ...(term ? { q: term } : {}),
            ...(category ? { category } : {}),
        });
    }

    return (
        <form
            key={q ?? ''}
            role="search"
            action={search().url}
            method="get"
            onSubmit={handleSubmit}
            className="flex w-full max-w-xl overflow-hidden rounded-full border border-neutral-300 bg-white focus-within:border-accent-600 focus-within:ring-2 focus-within:ring-accent-100"
        >
            <input
                type="search"
                name="q"
                defaultValue={q ?? ''}
                placeholder="Search products..."
                aria-label="Search products"
                autoComplete="off"
                className="h-11 min-w-0 flex-1 bg-transparent pl-4 text-sm outline-none placeholder:text-neutral-400"
            />
            {category && (
                <input type="hidden" name="category" value={category} />
            )}
            <button
                type="submit"
                aria-label="Search"
                className="flex h-11 w-12 items-center justify-center text-neutral-600 transition-colors hover:text-accent-600"
            >
                <Icon name="search" size={18} />
            </button>
        </form>
    );
}

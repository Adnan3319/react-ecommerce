import { router, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { search } from '@/routes';
import { Icon } from '@/storefront/components/ui';

function readQuery(url: string): string {
    const queryString = url.split('?')[1] ?? '';

    return new URLSearchParams(queryString).get('q') ?? '';
}

export default function SearchBar() {
    const { url, props } = usePage<{ category?: { slug?: string } | null }>();
    // Searching from a category page stays inside that category.
    const category = props.category?.slug ?? null;
    const currentQuery = readQuery(url);

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const value = new FormData(event.currentTarget).get('q');
        const q = typeof value === 'string' ? value.trim() : '';

        router.get(search().url, {
            ...(q ? { q } : {}),
            ...(category ? { category } : {}),
        });
    }

    return (
        <form
            key={currentQuery}
            role="search"
            action={search().url}
            method="get"
            onSubmit={handleSubmit}
            className="flex w-full overflow-hidden rounded-full border border-white/20 bg-white/10 text-white backdrop-blur focus-within:border-white/50"
        >
            {category && (
                <input type="hidden" name="category" value={category} />
            )}
            <input
                type="search"
                name="q"
                defaultValue={currentQuery}
                placeholder="Search for products..."
                aria-label="Search products"
                autoComplete="off"
                className="h-10 min-w-0 flex-1 bg-transparent pl-4 text-sm outline-none placeholder:text-white/60"
            />
            <button
                type="submit"
                aria-label="Search"
                className="flex h-10 w-11 items-center justify-center text-white/80 transition-colors hover:text-white"
            >
                <Icon name="search" size={18} />
            </button>
        </form>
    );
}

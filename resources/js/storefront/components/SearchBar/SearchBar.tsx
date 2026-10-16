import { router, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { search } from '@/routes';
import { Icon } from '@/shared/ui';

function readQuery(url: string): string {
    const queryString = url.split('?')[1] ?? '';

    return new URLSearchParams(queryString).get('q') ?? '';
}

export default function SearchBar() {
    const { url } = usePage();
    const currentQuery = readQuery(url);

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const value = new FormData(event.currentTarget).get('q');
        const q = typeof value === 'string' ? value.trim() : '';

        router.get(search().url, q ? { q } : {});
    }

    return (
        <form
            key={currentQuery}
            role="search"
            action={search().url}
            method="get"
            onSubmit={handleSubmit}
            className="flex w-full overflow-hidden rounded-control border border-neutral-300 bg-white focus-within:border-accent-600 focus-within:ring-2 focus-within:ring-accent-100"
        >
            <input
                type="search"
                name="q"
                defaultValue={currentQuery}
                placeholder="Search products"
                aria-label="Search products"
                autoComplete="off"
                className="h-11 min-w-0 flex-1 bg-transparent px-4 text-base outline-none placeholder:text-neutral-500"
            />
            <button
                type="submit"
                aria-label="Search"
                className="flex h-11 w-12 items-center justify-center bg-accent-600 text-white transition-colors hover:bg-accent-700"
            >
                <Icon name="search" size={20} />
            </button>
        </form>
    );
}

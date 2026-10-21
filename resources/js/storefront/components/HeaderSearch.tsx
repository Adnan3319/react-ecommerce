import { usePage } from '@inertiajs/react';
import SearchBox from './SearchBox';

export const HEADER_SEARCH_INPUT_ID = 'header-search-input';

function readQuery(url: string): string {
    const queryString = url.split('?')[1] ?? '';

    return new URLSearchParams(queryString).get('q') ?? '';
}

type HeaderSearchProps = {
    onNavigate: () => void;
};

/** The header's search box; on a category page the search stays inside that category. */
export default function HeaderSearch({ onNavigate }: HeaderSearchProps) {
    const { url, props } = usePage<{ category?: { slug?: string } | null }>();
    const query = readQuery(url);

    return (
        <SearchBox
            key={query}
            defaultQuery={query}
            category={props.category?.slug ?? null}
            tone="dark"
            inputId={HEADER_SEARCH_INPUT_ID}
            onNavigate={onNavigate}
        />
    );
}

import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/shared/lib';

type PaginationProps = {
    current: number;
    last: number;
};

const PAGE_WINDOW = 1;

const ITEM =
    'flex size-10 items-center justify-center rounded-control text-sm font-medium transition-colors';

/** Page numbers to show: first, last and the neighbours of the current page, with gaps as null. */
function visiblePages(current: number, last: number): (number | null)[] {
    const pages: (number | null)[] = [];

    for (let page = 1; page <= last; page += 1) {
        const near = Math.abs(page - current) <= PAGE_WINDOW;

        if (page === 1 || page === last || near) {
            pages.push(page);
        } else if (pages[pages.length - 1] !== null) {
            pages.push(null);
        }
    }

    return pages;
}

/** Real links, so crawlers and no-JS visitors can walk the pages; filters stay in the query string. */
export default function Pagination({ current, last }: PaginationProps) {
    const { url } = usePage();

    if (last <= 1) {
        return null;
    }

    function hrefFor(page: number): string {
        const [path, query = ''] = url.split('?');
        const params = new URLSearchParams(query);

        if (page === 1) {
            params.delete('page');
        } else {
            params.set('page', String(page));
        }

        const queryString = params.toString();

        return queryString ? `${path}?${queryString}` : path;
    }

    return (
        <nav aria-label="Pagination" className="mt-8 flex justify-center">
            <ul className="flex flex-wrap items-center gap-1">
                {current > 1 && (
                    <li>
                        <Link
                            href={hrefFor(current - 1)}
                            rel="prev"
                            aria-label="Previous page"
                            className={cn(
                                ITEM,
                                'bg-white hover:bg-neutral-100',
                            )}
                        >
                            ‹
                        </Link>
                    </li>
                )}
                {visiblePages(current, last).map((page, index) =>
                    page === null ? (
                        <li
                            key={`gap-${index}`}
                            aria-hidden="true"
                            className={cn(ITEM, 'text-neutral-500')}
                        >
                            …
                        </li>
                    ) : (
                        <li key={page}>
                            <Link
                                href={hrefFor(page)}
                                aria-current={
                                    page === current ? 'page' : undefined
                                }
                                className={cn(
                                    ITEM,
                                    page === current
                                        ? 'bg-neutral-900 text-white'
                                        : 'bg-white hover:bg-neutral-100',
                                )}
                            >
                                {page}
                            </Link>
                        </li>
                    ),
                )}
                {current < last && (
                    <li>
                        <Link
                            href={hrefFor(current + 1)}
                            rel="next"
                            aria-label="Next page"
                            className={cn(
                                ITEM,
                                'bg-white hover:bg-neutral-100',
                            )}
                        >
                            ›
                        </Link>
                    </li>
                )}
            </ul>
        </nav>
    );
}

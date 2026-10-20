import { cn } from '@admin/utils';

interface PaginationProps {
    currentPage: number;
    lastPage: number;
    onPageChange: (page: number) => void;
    className?: string;
}

const SIBLINGS = 1;
const ELLIPSIS = 'ellipsis';

type PageItem = number | typeof ELLIPSIS;

function buildPages(currentPage: number, lastPage: number): PageItem[] {
    const pages: PageItem[] = [];

    for (let page = 1; page <= lastPage; page++) {
        const isEdge = page === 1 || page === lastPage;
        const isNearCurrent = Math.abs(page - currentPage) <= SIBLINGS;

        if (isEdge || isNearCurrent) {
            pages.push(page);
        } else if (pages[pages.length - 1] !== ELLIPSIS) {
            pages.push(ELLIPSIS);
        }
    }

    return pages;
}

const BUTTON =
    'inline-flex h-10 min-w-10 items-center justify-center rounded-lg px-3 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50';
const IDLE =
    'text-gray-700 ring-1 ring-gray-300 ring-inset hover:bg-gray-50 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/3';

export default function Pagination({
    currentPage,
    lastPage,
    onPageChange,
    className,
}: PaginationProps) {
    if (lastPage <= 1) {
        return null;
    }

    return (
        <nav
            aria-label="Pagination"
            className={cn('flex flex-wrap items-center gap-1.5', className)}
        >
            <button
                type="button"
                className={cn(BUTTON, IDLE)}
                disabled={currentPage <= 1}
                onClick={() => onPageChange(currentPage - 1)}
            >
                Previous
            </button>

            {buildPages(currentPage, lastPage).map((item, index) =>
                item === ELLIPSIS ? (
                    <span
                        key={`ellipsis-${index}`}
                        aria-hidden="true"
                        className="px-1 text-gray-400"
                    >
                        …
                    </span>
                ) : (
                    <button
                        key={item}
                        type="button"
                        aria-current={item === currentPage ? 'page' : undefined}
                        className={cn(
                            BUTTON,
                            item === currentPage
                                ? 'bg-brand-500 text-white'
                                : IDLE,
                        )}
                        onClick={() => onPageChange(item)}
                    >
                        {item}
                    </button>
                ),
            )}

            <button
                type="button"
                className={cn(BUTTON, IDLE)}
                disabled={currentPage >= lastPage}
                onClick={() => onPageChange(currentPage + 1)}
            >
                Next
            </button>
        </nav>
    );
}

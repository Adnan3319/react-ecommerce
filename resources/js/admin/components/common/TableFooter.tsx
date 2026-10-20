import Pagination from '@admin/components/ui/pagination/Pagination';
import type { PaginationMeta } from '@/shared/types';

interface TableFooterProps {
    meta: PaginationMeta;
    onPageChange: (page: number) => void;
}

/** "Showing 1–10 of 42" and the page buttons under a paginated DataTable. */
export default function TableFooter({ meta, onPageChange }: TableFooterProps) {
    return (
        <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
            <p className="text-sm text-gray-500">
                {meta.total > 0
                    ? `Showing ${meta.from}–${meta.to} of ${meta.total}`
                    : 'No results'}
            </p>
            <Pagination
                currentPage={meta.current_page}
                lastPage={meta.last_page}
                onPageChange={onPageChange}
            />
        </div>
    );
}

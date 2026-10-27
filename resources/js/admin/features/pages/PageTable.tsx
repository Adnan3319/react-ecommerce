import StatusToggle from '@admin/components/common/StatusToggle';
import DataTable from '@admin/components/DataTable';
import type { DataTableToolbar } from '@admin/components/DataTable';
import { STATUS_ACTIVE } from '@/shared/constants/status';
import type { Page } from './types';

type PageTableProps = {
    pages: Page[];
    loading: boolean;
    toolbar: DataTableToolbar;
    busyId: number | null;
    onEdit: (page: Page) => void;
    onDelete: (page: Page) => void;
    onToggleStatus: (page: Page) => void;
};

export default function PageTable({
    pages,
    loading,
    toolbar,
    busyId,
    onEdit,
    onDelete,
    onToggleStatus,
}: PageTableProps) {
    const columns = [
        {
            header: 'Title',
            accessor: 'title',
            render: (row: Page) => (
                <p className="font-medium text-gray-900 dark:text-white/90">
                    {row.title}
                </p>
            ),
        },
        {
            header: 'Sub Title',
            accessor: 'sub_title',
            render: (row: Page) => (
                <span className="text-gray-600 dark:text-gray-400">
                    {row.sub_title || '-'}
                </span>
            ),
        },
        { header: 'Slug', accessor: 'slug' },
        {
            header: 'Status',
            accessor: 'status',
            render: (row: Page) => (
                <StatusToggle
                    isActive={row.status === STATUS_ACTIVE}
                    disabled={busyId === row.id}
                    onToggle={() => onToggleStatus(row)}
                />
            ),
        },
        {
            header: 'Actions',
            accessor: 'actions',
            render: (row: Page) => (
                <div className="flex items-center gap-4 text-sm font-medium">
                    <button
                        type="button"
                        onClick={() => onEdit(row)}
                        className="text-brand-500 hover:text-brand-600"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        onClick={() => onDelete(row)}
                        className="text-error-500 hover:text-error-600"
                    >
                        Delete
                    </button>
                </div>
            ),
        },
    ];

    return (
        <DataTable
            columns={columns}
            data={pages}
            loading={loading}
            toolbar={toolbar}
            noDataMessage="No pages found."
        />
    );
}
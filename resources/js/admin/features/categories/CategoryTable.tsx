import StatusToggle from '@admin/components/common/StatusToggle';
import DataTable from '@admin/components/DataTable';
import { STATUS_ACTIVE } from '@/shared/constants/status';
import type { Category } from './types';

type CategoryTableProps = {
    categories: Category[];
    loading: boolean;
    busyId: number | null;
    onEdit: (category: Category) => void;
    onDelete: (category: Category) => void;
    onToggleStatus: (category: Category) => void;
};

export default function CategoryTable({
    categories,
    loading,
    busyId,
    onEdit,
    onDelete,
    onToggleStatus,
}: CategoryTableProps) {
    const columns = [
        {
            header: 'Image',
            accessor: 'image_url',
            render: (row: Category) =>
                row.image_url ? (
                    <img
                        src={row.image_url}
                        alt={row.name}
                        width={48}
                        height={48}
                        loading="lazy"
                        className="h-12 w-12 rounded-md bg-gray-100 object-cover dark:bg-white/5"
                    />
                ) : (
                    <div className="flex h-12 w-12 items-center justify-center rounded-md bg-gray-100 text-[10px] text-gray-400 dark:bg-white/5">
                        None
                    </div>
                ),
        },
        {
            header: 'Name',
            accessor: 'name',
            render: (row: Category) => (
                <div>
                    <p className="font-medium text-gray-900 dark:text-white/90">{row.name}</p>
                    {row.headline && <p className="text-xs text-gray-500">{row.headline}</p>}
                </div>
            ),
        },
        { header: 'Slug', accessor: 'slug' },
        { header: 'Order', accessor: 'sort_order' },
        {
            header: 'Status',
            accessor: 'status',
            render: (row: Category) => (
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
            render: (row: Category) => (
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
            data={categories}
            loading={loading}
            noDataMessage="No categories found."
        />
    );
}

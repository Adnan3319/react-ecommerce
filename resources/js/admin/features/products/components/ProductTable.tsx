import { Link } from '@inertiajs/react';
import StatusToggle from '@admin/components/common/StatusToggle';
import DataTable from '@admin/components/DataTable';
import type { DataTableToolbar } from '@admin/components/DataTable';
import Badge from '@admin/components/ui/badge/Badge';
import { STATUS_ACTIVE } from '@/shared/constants/status';
import { edit } from '@/routes/admin/products';
import type { ProductListItem } from '../types';

type ProductTableProps = {
    products: ProductListItem[];
    loading: boolean;
    toolbar: DataTableToolbar;
    busyId: number | null;
    onDelete: (product: ProductListItem) => void;
    onToggleStatus: (product: ProductListItem) => void;
    onToggleFeatured: (product: ProductListItem) => void;
};

export default function ProductTable({
    products,
    loading,
    toolbar,
    busyId,
    onDelete,
    onToggleStatus,
    onToggleFeatured,
}: ProductTableProps) {
    const columns = [
        {
            header: 'Image',
            accessor: 'thumb_url',
            render: (row: ProductListItem) =>
                row.thumb_url ? (
                    <img
                        src={row.thumb_url}
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
            render: (row: ProductListItem) => (
                <Link href={edit(row.id).url} className="font-medium text-gray-900 hover:text-brand-500 dark:text-white/90">
                    {row.name}
                </Link>
            ),
        },
        { header: 'Category', accessor: 'category' },
        { header: 'Price', accessor: 'price' },
        {
            header: 'Stock',
            accessor: 'total_stock',
            render: (row: ProductListItem) => (
                <div className="flex items-center gap-2">
                    <span>{row.total_stock}</span>
                    {row.stock_state === 'low' && (
                        <Badge size="sm" color="warning">
                            Low
                        </Badge>
                    )}
                    {row.stock_state === 'out' && (
                        <Badge size="sm" color="error">
                            Out
                        </Badge>
                    )}
                </div>
            ),
        },
        {
            header: 'Status',
            accessor: 'status',
            render: (row: ProductListItem) => (
                <StatusToggle
                    isActive={row.status === STATUS_ACTIVE}
                    disabled={busyId === row.id}
                    onToggle={() => onToggleStatus(row)}
                />
            ),
        },
        {
            header: 'Featured',
            accessor: 'is_featured',
            render: (row: ProductListItem) => (
                <StatusToggle
                    isActive={row.is_featured}
                    disabled={busyId === row.id}
                    activeLabel="Featured"
                    inactiveLabel="No"
                    onToggle={() => onToggleFeatured(row)}
                />
            ),
        },
        {
            header: 'Actions',
            accessor: 'actions',
            render: (row: ProductListItem) => (
                <div className="flex items-center gap-4 text-sm font-medium">
                    <Link href={edit(row.id).url} className="text-brand-500 hover:text-brand-600">
                        Edit
                    </Link>
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
            data={products}
            loading={loading}
            toolbar={toolbar}
            noDataMessage="No products found."
        />
    );
}

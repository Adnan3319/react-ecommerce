import { Link } from '@inertiajs/react';
import DataTable from '@admin/components/DataTable';
import type { DataTableToolbar } from '@admin/components/DataTable';
import Badge from '@admin/components/ui/badge/Badge';
import { show } from '@/routes/admin/orders';
import { formatShort } from '@admin/lib/format-date';
import type { OrderListItem } from './types';

type OrderTableProps = {
    orders: OrderListItem[];
    loading: boolean;
    toolbar: DataTableToolbar;
};

const columns = [
    {
        header: 'Order',
        accessor: 'number',
        render: (row: OrderListItem) => (
            <Link
                href={show(row.id).url}
                className="hover:text-brand-500 font-semibold text-gray-900 dark:text-white/90"
            >
                {row.number}
            </Link>
        ),
    },
    {
        header: 'Date',
        accessor: 'placed_at',
        render: (row: OrderListItem) => formatShort(row.placed_at),
    },
    {
        header: 'Customer',
        accessor: 'customer_name',
        render: (row: OrderListItem) => (
            <div>
                <div>{row.customer_name}</div>
                <div className="text-xs text-gray-500">
                    {row.customer_phone} · {row.delivery_zone_label}
                </div>
            </div>
        ),
    },
    { header: 'Items', accessor: 'items_count' },
    {
        header: 'Total',
        accessor: 'total',
        render: (row: OrderListItem) => (
            <span className="font-semibold">{row.total}</span>
        ),
    },
    {
        header: 'Source',
        accessor: 'source',
        render: (row: OrderListItem) => (
            <Badge size="sm">{row.source.label}</Badge>
        ),
    },
    {
        header: 'Status',
        accessor: 'status',
        render: (row: OrderListItem) => (
            <Badge size="sm" color={row.status.color}>
                {row.status.label}
            </Badge>
        ),
    },
    {
        header: 'Actions',
        accessor: 'actions',
        render: (row: OrderListItem) => (
            <Link
                href={show(row.id).url}
                className="text-brand-500 hover:text-brand-600 text-sm font-medium"
            >
                View
            </Link>
        ),
    },
];

export default function OrderTable({
    orders,
    loading,
    toolbar,
}: OrderTableProps) {
    return (
        <DataTable
            columns={columns}
            data={orders}
            loading={loading}
            toolbar={toolbar}
            noDataMessage="No orders found."
        />
    );
}

import { Link } from '@inertiajs/react';
import DataTable from '@admin/components/DataTable';
import TableFooter from '@admin/components/common/TableFooter';
import Select from '@admin/components/form/Select';
import Badge from '@admin/components/ui/badge/Badge';
import { useListFilters } from '@admin/hooks/useListFilters';
import { formatShort } from '@admin/lib/format-date';
import { index, show } from '@/routes/admin/messages';
import { FILTER_ALL, READ_FILTER_OPTIONS } from './constants';
import type { ContactMessage, MessageIndexProps } from './types';

const columns = [
    {
        header: 'From',
        accessor: 'name',
        render: (row: ContactMessage) => (
            <Link
                href={show(row.id).url}
                className="hover:text-brand-500 flex items-center gap-2 font-semibold text-gray-900 dark:text-white/90"
            >
                {!row.is_read && (
                    <span
                        role="img"
                        aria-label="Unread"
                        className="bg-brand-500 size-2 shrink-0 rounded-full"
                    />
                )}
                {row.name}
            </Link>
        ),
    },
    { header: 'Mobile', accessor: 'mobile' },
    {
        header: 'Message',
        accessor: 'message',
        render: (row: ContactMessage) => (
            <span className="block max-w-md truncate">{row.message}</span>
        ),
    },
    {
        header: 'Received',
        accessor: 'created_at',
        render: (row: ContactMessage) => formatShort(row.created_at),
    },
    {
        header: 'Status',
        accessor: 'is_read',
        render: (row: ContactMessage) => (
            <Badge size="sm" color={row.is_read ? 'light' : 'primary'}>
                {row.is_read ? 'Read' : 'Unread'}
            </Badge>
        ),
    },
];

export default function MessageManager({
    messages,
    filters,
}: MessageIndexProps) {
    // The hook's own status param is the ActiveStatus one; the read filter travels as `read`.
    const {
        search,
        setSearch,
        setExtra,
        perPage,
        setPerPage,
        goToPage,
        processing,
    } = useListFilters(
        index().url,
        { ...filters, status: null },
        { read: filters.read ?? undefined },
    );

    return (
        <div className="w-full">
            <h1 className="mb-6 text-2xl font-bold text-gray-900 dark:text-white/90">
                Messages
            </h1>

            <DataTable
                columns={columns}
                data={messages.data}
                loading={processing}
                noDataMessage="No messages found."
                toolbar={{
                    search,
                    onSearchChange: setSearch,
                    searchPlaceholder: 'Search name, mobile or email',
                    perPage,
                    onPerPageChange: setPerPage,
                    filters: (
                        <div
                            className="sm:w-44"
                            aria-label="Filter by read state"
                        >
                            <Select
                                options={READ_FILTER_OPTIONS}
                                defaultValue={filters.read ?? FILTER_ALL}
                                onChange={(value) =>
                                    setExtra({
                                        read:
                                            value === FILTER_ALL
                                                ? undefined
                                                : value,
                                    })
                                }
                            />
                        </div>
                    ),
                }}
            />

            <TableFooter meta={messages.meta} onPageChange={goToPage} />
        </div>
    );
}

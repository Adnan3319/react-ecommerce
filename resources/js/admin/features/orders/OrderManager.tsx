import TableFooter from '@admin/components/common/TableFooter';
import { useListFilters } from '@admin/hooks/useListFilters';
import { index } from '@/routes/admin/orders';
import { FILTER_ALL } from './constants';
import OrderFilters from './OrderFilters';
import OrderTable from './OrderTable';
import StatusTabs from './StatusTabs';
import type { OrderIndexProps } from './types';

export default function OrderManager({
    orders,
    filters,
    statuses,
    sources,
}: OrderIndexProps) {
    // The hook's own status param is the ActiveStatus one; order statuses travel as `order_status`.
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
        {
            order_status: filters.order_status ?? undefined,
            source: filters.source ?? undefined,
            range: filters.range ?? undefined,
        },
    );

    return (
        <div className="w-full">
            <h1 className="mb-6 text-2xl font-bold text-gray-900 dark:text-white/90">
                Orders
            </h1>

            <StatusTabs
                statuses={statuses}
                active={filters.order_status}
                onChange={(status) =>
                    setExtra({
                        order_status:
                            status === FILTER_ALL ? undefined : status,
                    })
                }
            />

            <OrderTable
                orders={orders.data}
                loading={processing}
                toolbar={{
                    search,
                    onSearchChange: setSearch,
                    searchPlaceholder: 'Search order no, name or phone',
                    perPage,
                    onPerPageChange: setPerPage,
                    filters: (
                        <OrderFilters
                            filters={filters}
                            sources={sources}
                            onExtraChange={setExtra}
                        />
                    ),
                }}
            />

            <TableFooter meta={orders.meta} onPageChange={goToPage} />
        </div>
    );
}

import { Link } from '@inertiajs/react';
import Badge from '@admin/components/ui/badge/Badge';
import { formatShort } from '@admin/lib/format-date';
import { show } from '@/routes/admin/orders';
import type { DashboardProps } from './types';

export default function RecentOrdersTable({
    recent,
}: {
    recent: DashboardProps['recent'];
}) {
    return (
        <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/3">
            <h2 className="mb-4 text-lg font-semibold text-gray-900 dark:text-white/90">
                Recent orders
            </h2>

            {recent.length === 0 ? (
                <p className="text-sm text-gray-500">No orders yet.</p>
            ) : (
                <ul className="divide-y divide-gray-200 dark:divide-gray-800">
                    {recent.map((order) => (
                        <li
                            key={order.id}
                            className="flex items-center justify-between gap-3 py-3"
                        >
                            <div className="min-w-0">
                                <Link
                                    href={show(order.id).url}
                                    className="hover:text-brand-500 font-semibold text-gray-900 dark:text-white/90"
                                >
                                    {order.number}
                                </Link>
                                <p className="truncate text-xs text-gray-500">
                                    {order.customer_name} ·{' '}
                                    {formatShort(order.placed_at)}
                                </p>
                            </div>
                            <div className="flex items-center gap-3">
                                <span className="text-sm font-medium text-gray-900 dark:text-white/90">
                                    {order.total}
                                </span>
                                <Badge size="sm" color={order.status.color}>
                                    {order.status.label}
                                </Badge>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

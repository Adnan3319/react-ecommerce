import { Link } from '@inertiajs/react';
import { index } from '@/routes/admin/orders';
import type { DashboardProps } from './types';

type Metric = { label: string; value: string | number; hint: string };

export default function TodayMetrics({
    today,
}: {
    today: DashboardProps['today'];
}) {
    const metrics: Metric[] = [
        {
            label: 'Orders today',
            value: today.orders,
            hint: 'Placed since midnight',
        },
        {
            label: 'Revenue today',
            value: today.revenue,
            hint: 'Orders delivered today',
        },
        {
            label: 'Pending orders',
            value: today.pending,
            hint: 'Waiting for confirmation',
        },
    ];

    return (
        <section aria-label="Today" className="grid gap-4 sm:grid-cols-3">
            {metrics.map((metric) => (
                <div
                    key={metric.label}
                    className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/3"
                >
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        {metric.label}
                    </p>
                    <p className="mt-2 text-3xl font-bold text-gray-900 dark:text-white/90">
                        {metric.value}
                    </p>
                    <p className="mt-1 text-xs text-gray-500">{metric.hint}</p>
                </div>
            ))}
            <Link
                href={index().url}
                className="text-brand-500 hover:text-brand-600 text-sm font-medium sm:col-span-3"
            >
                View all orders
            </Link>
        </section>
    );
}

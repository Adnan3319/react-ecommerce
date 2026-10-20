import { cn } from '@admin/utils';
import type { CustomerHistoryCounts } from './types';

export default function CustomerHistory({ history }: { history: CustomerHistoryCounts }) {
    const stats = [
        { label: 'Delivered', value: history.delivered, warn: false },
        { label: 'Cancelled', value: history.cancelled, warn: history.cancelled > 0 },
        { label: 'Pending', value: history.pending, warn: false },
    ];

    return (
        <>
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">Order history (this phone)</h2>
            <p className="mb-4 text-sm text-gray-500">Helps spot fake orders.</p>
            <dl className="grid grid-cols-3 gap-3 text-center">
                {stats.map((stat) => (
                    <div key={stat.label} className="rounded-lg bg-gray-50 px-2 py-3 dark:bg-white/5">
                        <dd
                            className={cn(
                                'text-xl font-semibold',
                                stat.warn ? 'text-error-600' : 'text-gray-900 dark:text-white/90',
                            )}
                        >
                            {stat.value}
                        </dd>
                        <dt className="text-sm text-gray-600 dark:text-gray-400">{stat.label}</dt>
                    </div>
                ))}
            </dl>
        </>
    );
}

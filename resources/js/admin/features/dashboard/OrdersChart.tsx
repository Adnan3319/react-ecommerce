import { MIN_BAR_PERCENT, TREND_DAYS } from './constants';
import type { DashboardProps } from './types';

/** Orders per day for the last 30 days as plain bars (no chart library, nothing to load). */
export default function OrdersChart({
    trend,
}: {
    trend: DashboardProps['trend'];
}) {
    const max = Math.max(1, ...trend.map((day) => day.orders));
    const total = trend.reduce((sum, day) => sum + day.orders, 0);

    return (
        <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/3">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">
                Orders, last {TREND_DAYS} days
            </h2>
            <p className="mb-4 text-sm text-gray-500">{total} orders placed</p>

            <div
                role="img"
                aria-label={`Orders per day for the last ${TREND_DAYS} days, ${total} in total`}
                className="flex h-44 items-end gap-1"
            >
                {trend.map((day) => (
                    <div
                        key={day.date}
                        title={`${day.date}: ${day.orders}`}
                        className="flex h-full flex-1 items-end"
                    >
                        <div
                            className={
                                day.orders > 0
                                    ? 'bg-brand-500 w-full rounded-t'
                                    : 'w-full rounded-t bg-gray-200 dark:bg-gray-700'
                            }
                            style={{
                                height: `${Math.max(MIN_BAR_PERCENT, (day.orders / max) * 100)}%`,
                            }}
                        />
                    </div>
                ))}
            </div>
            <div className="mt-2 flex justify-between text-xs text-gray-500">
                <span>{trend[0]?.date}</span>
                <span>{trend[trend.length - 1]?.date}</span>
            </div>
        </section>
    );
}

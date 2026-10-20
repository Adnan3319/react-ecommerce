import { TREND_DAYS } from './constants';
import type { DashboardProps } from './types';

export default function OutcomeRatio({
    outcome,
}: {
    outcome: DashboardProps['outcome'];
}) {
    const percent = outcome.delivered_percent;

    return (
        <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/3">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">
                Delivered vs cancelled
            </h2>
            <p className="mb-4 text-sm text-gray-500">
                Orders placed in the last {TREND_DAYS} days that are closed
            </p>

            {percent === null ? (
                <p className="text-sm text-gray-500">No closed orders yet.</p>
            ) : (
                <>
                    <p className="text-3xl font-bold text-gray-900 dark:text-white/90">
                        {percent}% delivered
                    </p>
                    <div
                        role="img"
                        aria-label={`${outcome.delivered} delivered, ${outcome.cancelled} cancelled`}
                        className="mt-3 flex h-3 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"
                    >
                        <div
                            className="bg-success-500"
                            style={{ width: `${percent}%` }}
                        />
                        <div className="bg-error-500 flex-1" />
                    </div>
                    <dl className="mt-3 flex justify-between text-sm">
                        <div>
                            <dt className="text-gray-500">Delivered</dt>
                            <dd className="font-semibold text-gray-900 dark:text-white/90">
                                {outcome.delivered}
                            </dd>
                        </div>
                        <div className="text-end">
                            <dt className="text-gray-500">Cancelled</dt>
                            <dd className="font-semibold text-gray-900 dark:text-white/90">
                                {outcome.cancelled}
                            </dd>
                        </div>
                    </dl>
                </>
            )}
        </section>
    );
}

import { Link } from '@inertiajs/react';
import Badge from '@admin/components/ui/badge/Badge';
import { index } from '@/routes/admin/orders';
import BlockPhoneButton from './BlockPhoneButton';
import CustomerCard from './CustomerCard';
import CustomerHistory from './CustomerHistory';
import { formatFull } from '@admin/lib/format-date';
import OrderItems from './OrderItems';
import OrderNoteForm from './OrderNoteForm';
import StatusActions from './StatusActions';
import StatusTimeline from './StatusTimeline';
import type { OrderDetailData } from './types';

const CARD =
    'rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/3';

/** "from Facebook ad (utm_campaign=summer_dress)" built from whatever tracking data was captured. */
function describeOrigin(order: OrderDetailData): string | null {
    const parts = [
        order.utm_source ? `from ${order.utm_source}` : null,
        order.utm_campaign ? `(utm_campaign=${order.utm_campaign})` : null,
        order.fbclid ? '(fbclid)' : null,
    ].filter(Boolean);

    return parts.length > 0 ? parts.join(' ') : null;
}

export default function OrderDetail({ order }: { order: OrderDetailData }) {
    const origin = describeOrigin(order);

    return (
        <div className="w-full">
            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p className="text-sm text-gray-500">
                        <Link
                            href={index().url}
                            className="hover:text-brand-500"
                        >
                            Orders
                        </Link>{' '}
                        / {order.number}
                    </p>
                    <div className="mt-1 flex flex-wrap items-center gap-3">
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">
                            {order.number}
                        </h1>
                        <Badge color={order.status.color}>
                            {order.status.label}
                        </Badge>
                    </div>
                    <p className="mt-1 text-sm text-gray-500">
                        {formatFull(order.placed_at)} · {order.source.label}
                        {origin ? ` · ${origin}` : ''}
                    </p>
                </div>
                <StatusActions order={order} />
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <section className={CARD}>
                        <OrderItems order={order} />
                    </section>
                    <section className={CARD}>
                        <StatusTimeline history={order.history} />
                        <OrderNoteForm orderId={order.id} />
                    </section>
                </div>

                <div className="space-y-6">
                    <section className={CARD}>
                        <CustomerCard order={order} />
                    </section>
                    <section className={CARD}>
                        <CustomerHistory history={order.customer_history} />
                    </section>
                    <section className={CARD}>
                        <BlockPhoneButton customer={order.customer} />
                    </section>
                </div>
            </div>
        </div>
    );
}

import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import { formatMoney } from '@/shared/lib';
import { Icon, Image } from '@/storefront/components/ui';
import type { PlacedOrder } from './types';

const THUMB_SIZE = 120;

type OrderSuccessProps = {
    order: PlacedOrder;
};

/** Thank-you page after an order: number, what happens next and the order lines. */
export default function OrderSuccess({ order }: OrderSuccessProps) {
    return (
        <section className="mx-auto flex max-w-xl flex-col items-center gap-5 px-4 py-14 text-center sm:px-6">
            <span className="flex h-20 w-20 items-center justify-center rounded-full bg-green-100 text-green-700">
                <Icon name="check" size={36} strokeWidth={3} />
            </span>

            <div>
                <h1 className="text-3xl font-extrabold tracking-tight text-neutral-900">
                    Thank you, {order.first_name}!
                </h1>
                <p className="mt-2 text-neutral-600">Your order is placed.</p>
            </div>

            <p className="rounded-control border border-dashed border-neutral-300 bg-white px-5 py-3 text-xl font-bold text-neutral-900">
                Order #{order.number}
            </p>

            <p className="flex w-full items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-start text-sm text-amber-900">
                <Icon name="phone" size={20} className="mt-0.5 shrink-0" />
                <span>
                    <strong>We will call {order.phone}</strong> within a few
                    hours to confirm your order. Please keep your phone close.
                </span>
            </p>

            <div className="w-full rounded-[24px] bg-white p-5 text-start shadow-[0_8px_30px_rgba(15,23,42,0.06)]">
                <ul className="divide-y divide-neutral-100">
                    {order.items.map((item) => (
                        <li
                            key={`${item.name}-${item.variant_label}`}
                            className="flex items-center gap-3 py-3"
                        >
                            <span className="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-neutral-100">
                                {item.image_url && (
                                    <Image
                                        src={item.image_url}
                                        alt=""
                                        width={THUMB_SIZE}
                                        height={THUMB_SIZE}
                                        className="h-full w-full object-cover"
                                    />
                                )}
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className="block truncate font-semibold text-neutral-900">
                                    {item.name}
                                </span>
                                <span className="block text-sm text-neutral-500">
                                    {[
                                        item.variant_label,
                                        `Qty ${item.quantity}`,
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </span>
                            </span>
                            <span className="font-semibold text-neutral-900">
                                {formatMoney(item.line_total)}
                            </span>
                        </li>
                    ))}
                </ul>
                <dl className="mt-2 flex flex-col gap-1.5 border-t border-neutral-200 pt-3 text-sm">
                    <div className="flex justify-between text-neutral-600">
                        <dt>Delivery ({order.delivery_zone_label})</dt>
                        <dd>{formatMoney(order.delivery_charge)}</dd>
                    </div>
                    <div className="flex items-baseline justify-between text-neutral-900">
                        <dt className="text-base font-semibold">
                            Pay on delivery
                        </dt>
                        <dd className="text-lg font-bold">
                            {formatMoney(order.total)}
                        </dd>
                    </div>
                </dl>
            </div>

            <Link
                href={home()}
                className="inline-flex h-12 items-center rounded-control bg-neutral-900 px-7 text-sm font-semibold text-white hover:bg-neutral-800"
            >
                Continue shopping
            </Link>
        </section>
    );
}

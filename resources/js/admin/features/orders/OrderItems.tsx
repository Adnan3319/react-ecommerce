import type { OrderDetailData } from './types';

export default function OrderItems({ order }: { order: OrderDetailData }) {
    return (
        <>
            <h2 className="mb-4 text-lg font-semibold text-gray-900 dark:text-white/90">
                Items
            </h2>

            <ul className="divide-y divide-gray-200 dark:divide-gray-800">
                {order.items.map((item) => (
                    <li key={item.id} className="flex items-center gap-4 py-3">
                        {item.image_url ? (
                            <img
                                src={item.image_url}
                                alt={item.name}
                                width={52}
                                height={52}
                                loading="lazy"
                                className="h-13 w-13 rounded-md bg-gray-100 object-cover dark:bg-white/5"
                            />
                        ) : (
                            <div
                                className="h-13 w-13 rounded-md bg-gray-100 dark:bg-white/5"
                                aria-hidden
                            />
                        )}
                        <div className="min-w-0 flex-1">
                            <p className="font-medium text-gray-900 dark:text-white/90">
                                {item.name}
                            </p>
                            <p className="text-xs text-gray-500">
                                {item.variant_label
                                    ? `${item.variant_label} · `
                                    : ''}
                                SKU {item.sku}
                            </p>
                        </div>
                        <p className="text-sm text-gray-500">
                            {item.unit_price} × {item.quantity}
                        </p>
                        <p className="w-24 text-end font-semibold text-gray-900 dark:text-white/90">
                            {item.line_total}
                        </p>
                    </li>
                ))}
            </ul>

            <dl className="mt-4 space-y-2 border-t border-gray-200 pt-4 text-sm dark:border-gray-800">
                <div className="flex justify-between text-gray-600 dark:text-gray-400">
                    <dt>Subtotal</dt>
                    <dd>{order.subtotal}</dd>
                </div>
                <div className="flex justify-between text-gray-600 dark:text-gray-400">
                    <dt>Delivery ({order.delivery_zone_label})</dt>
                    <dd>{order.delivery_charge}</dd>
                </div>
                <div className="flex justify-between border-t border-gray-200 pt-3 text-base font-semibold text-gray-900 dark:border-gray-800 dark:text-white/90">
                    <dt>Total · Cash on delivery</dt>
                    <dd>{order.total}</dd>
                </div>
            </dl>
        </>
    );
}

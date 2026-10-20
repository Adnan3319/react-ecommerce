import { usePage } from '@inertiajs/react';
import OrderForm from '@/storefront/features/order/OrderForm';
import type { DeliveryZone } from '@/storefront/features/product/types';
import CartLineItem from './CartLineItem';
import EmptyCart from './EmptyCart';
import type { CartLine } from './types';

type CartViewProps = {
    lines: CartLine[];
    /** Minor units, payable lines only. */
    subtotal: number;
    deliveryZones: DeliveryZone[];
};

/** The Cart page body: the lines on the left, the shared order form on the right. */
export default function CartView({
    lines,
    subtotal,
    deliveryZones,
}: CartViewProps) {
    const { errors } = usePage().props;
    const itemCount = lines
        .filter((line) => !line.unavailable)
        .reduce((sum, line) => sum + line.quantity, 0);
    const changeError = errors.quantity ?? errors.cart ?? errors.variant;

    return (
        <section className="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            <h1 className="text-2xl font-bold tracking-tight text-neutral-900">
                Your cart
                {itemCount > 0 && (
                    <span className="ml-2 text-base font-medium text-neutral-500">
                        ({itemCount} {itemCount === 1 ? 'item' : 'items'})
                    </span>
                )}
            </h1>

            {lines.length === 0 ? (
                <div className="mt-6">
                    <EmptyCart />
                </div>
            ) : (
                <div className="mt-6 grid items-start gap-6 lg:grid-cols-[1.4fr_1fr]">
                    <div className="rounded-card bg-white p-5 shadow-[0_8px_30px_rgba(15,23,42,0.06)]">
                        {changeError && (
                            <p
                                role="alert"
                                className="mb-3 rounded-control bg-red-50 px-3 py-2 text-sm text-red-700"
                            >
                                {changeError}
                            </p>
                        )}
                        <ul className="divide-y divide-neutral-100">
                            {lines.map((line) => (
                                <CartLineItem
                                    key={`${line.variant_id}:${line.quantity}`}
                                    line={line}
                                />
                            ))}
                        </ul>
                    </div>
                    <div className="lg:sticky lg:top-24">
                        <OrderForm
                            zones={deliveryZones}
                            mode={{ source: 'cart', subtotal, itemCount }}
                        />
                    </div>
                </div>
            )}
        </section>
    );
}

import { formatMoney } from '@/shared/lib';

type OrderTotalsProps = {
    /** Integer minor units. */
    subtotal: number;
    /** Integer minor units. */
    deliveryCharge: number;
};

/** Display only; the backend recalculates every amount when the order is placed. */
export default function OrderTotals({
    subtotal,
    deliveryCharge,
}: OrderTotalsProps) {
    return (
        <dl className="flex flex-col gap-2 border-t border-neutral-200 pt-4 text-sm">
            <div className="flex justify-between">
                <dt className="text-neutral-600">Subtotal</dt>
                <dd className="font-medium text-neutral-900">
                    {formatMoney(subtotal)}
                </dd>
            </div>
            <div className="flex justify-between">
                <dt className="text-neutral-600">Delivery</dt>
                <dd className="font-medium text-neutral-900">
                    {formatMoney(deliveryCharge)}
                </dd>
            </div>
            <div className="flex items-baseline justify-between border-t border-neutral-200 pt-3">
                <dt className="font-semibold text-neutral-900">Total</dt>
                <dd className="text-xl font-bold text-neutral-900">
                    {formatMoney(subtotal + deliveryCharge)}
                </dd>
            </div>
        </dl>
    );
}

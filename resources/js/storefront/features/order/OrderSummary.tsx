import { formatMoney } from '@/shared/lib';

type OrderSummaryProps = {
    /** Minor units. */
    subtotal: number;
    /** Minor units. */
    deliveryCharge: number;
    itemCount: number;
};

/** Display only: the server recalculates every amount when the order is placed. */
export default function OrderSummary({ subtotal, deliveryCharge, itemCount }: OrderSummaryProps) {
    return (
        <dl className="flex flex-col gap-1.5 border-t border-dashed border-neutral-300 pt-4 text-sm">
            <div className="flex justify-between">
                <dt className="text-neutral-600">
                    Subtotal ({itemCount} {itemCount === 1 ? 'item' : 'items'})
                </dt>
                <dd className="text-neutral-900">{formatMoney(subtotal)}</dd>
            </div>
            <div className="flex justify-between">
                <dt className="text-neutral-600">Delivery</dt>
                <dd className="text-neutral-900">{formatMoney(deliveryCharge)}</dd>
            </div>
            <div className="flex items-baseline justify-between pt-1">
                <dt className="text-base font-semibold text-neutral-900">Total</dt>
                <dd className="text-lg font-bold text-neutral-900">{formatMoney(subtotal + deliveryCharge)}</dd>
            </div>
        </dl>
    );
}

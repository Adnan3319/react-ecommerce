import { formatMoney } from '@/shared/lib';
import { ORDER_FORM_ID } from '../OrderForm';

type MobileOrderBarProps = {
    /** Integer minor units. */
    price: number;
};

/** Sticky call-to-action above the mobile bottom nav. */
export default function MobileOrderBar({ price }: MobileOrderBarProps) {
    return (
        <div className="fixed inset-x-0 bottom-14 z-30 flex items-center justify-between gap-4 border-t border-neutral-200 bg-white px-4 py-2 pb-[calc(0.5rem+env(safe-area-inset-bottom))] md:hidden">
            <p className="text-lg font-bold text-neutral-900">
                {formatMoney(price)}
            </p>
            <a
                href={`#${ORDER_FORM_ID}`}
                className="inline-flex h-11 items-center rounded-control bg-accent-600 px-6 text-sm font-semibold text-white hover:bg-accent-700"
            >
                Order Now
            </a>
        </div>
    );
}

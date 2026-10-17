import { formatMoney } from '@/shared/lib';
import { Icon } from '@/shared/ui';
import { ORDER_FORM_ID } from '../OrderForm';

type MobileOrderBarProps = {
    /** Integer minor units. */
    price: number;
    /** Slot for the Add to Cart button, owned by the cart feature. */
    addToCart: React.ReactNode;
};

/** Sticky call-to-action above the mobile bottom nav. */
export default function MobileOrderBar({
    price,
    addToCart,
}: MobileOrderBarProps) {
    return (
        <div className="fixed inset-x-0 bottom-14 z-30 flex items-center gap-3 border-t border-neutral-200 bg-white px-4 py-2 pb-[calc(0.5rem+env(safe-area-inset-bottom))] md:hidden">
            <p className="mr-auto text-lg font-bold text-neutral-900">
                {formatMoney(price)}
            </p>
            {addToCart}
            <a
                href={`#${ORDER_FORM_ID}`}
                className="inline-flex h-11 items-center gap-2 rounded-control btn-accent px-5 text-sm font-semibold text-white"
            >
                <Icon name="bolt" size={16} />
                Order Now
            </a>
        </div>
    );
}

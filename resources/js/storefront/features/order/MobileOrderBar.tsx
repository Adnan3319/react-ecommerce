import { formatMoney } from '@/shared/lib';
import { Icon } from '@/storefront/components/ui';
import { ORDER_FORM_ID } from './OrderForm';

type MobileOrderBarProps = {
    /** Minor units; the total shown is display only. */
    total: number;
    disabled: boolean;
    onAddToCart: () => void;
    addingToCart: boolean;
};

/** Sticky call to action above the mobile bottom nav: jumps to the order form. */
export default function MobileOrderBar({
    total,
    disabled,
    onAddToCart,
    addingToCart,
}: MobileOrderBarProps) {
    return (
        <div className="fixed inset-x-0 bottom-14 z-30 flex items-center justify-between gap-3 border-t border-neutral-200 bg-white px-4 py-2 pb-[calc(0.5rem+env(safe-area-inset-bottom))] md:hidden">
            <div>
                <p className="text-xs text-neutral-500">Total</p>
                <p className="text-lg leading-tight font-bold text-neutral-900">
                    {formatMoney(total)}
                </p>
            </div>
            {disabled ? (
                <span className="inline-flex h-12 items-center rounded-control bg-neutral-200 px-6 text-sm font-semibold text-neutral-500">
                    Out of stock
                </span>
            ) : (
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={onAddToCart}
                        disabled={addingToCart}
                        aria-label="Add to cart"
                        className="inline-flex h-12 items-center justify-center rounded-control border border-neutral-300 px-4 text-neutral-800 disabled:opacity-50"
                    >
                        <Icon name="cart" size={18} />
                    </button>
                    <a
                        href={`#${ORDER_FORM_ID}`}
                        className="inline-flex h-12 items-center gap-2 rounded-control btn-accent px-6 text-sm font-semibold text-white"
                    >
                        <Icon name="bolt" size={16} />
                        Order Now
                    </a>
                </div>
            )}
        </div>
    );
}

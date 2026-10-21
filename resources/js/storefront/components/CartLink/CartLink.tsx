import { usePage } from '@inertiajs/react';
import type { SharedProps } from '@/shared/types';
import { Icon } from '@/storefront/components/ui';
import { useMiniCart } from '../MiniCartProvider';

/** Header cart button: opens the mini cart drawer and shows the number of items in the visitor's cart. */
export default function CartLink() {
    const { count } = usePage<SharedProps>().props.cart;
    const { open } = useMiniCart();

    return (
        <button
            type="button"
            onClick={open}
            aria-haspopup="dialog"
            aria-label={`Cart, ${count} ${count === 1 ? 'item' : 'items'}`}
            className="relative flex size-11 items-center justify-center rounded-full btn-accent"
        >
            <Icon
                name="cart"
                size={22}
                className="drop-shadow-[0_2px_1px_rgb(0_0_0/0.3)]"
            />
            {count > 0 && (
                <span className="absolute -top-2 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-neutral-900 px-1 text-xs font-semibold text-white ring-2 ring-white">
                    {count}
                </span>
            )}
        </button>
    );
}

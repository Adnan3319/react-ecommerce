import { Link, usePage } from '@inertiajs/react';
import { cart } from '@/routes';
import type { SharedProps } from '@/shared/types';
import { Icon } from '@/storefront/components/ui';

/** Header link to the cart page with the number of items in the visitor's cart. */
export default function CartLink() {
    const { count } = usePage<SharedProps>().props.cart;

    return (
        <Link
            href={cart()}
            aria-label={`Cart, ${count} ${count === 1 ? 'item' : 'items'}`}
            className="relative flex size-11 items-center justify-center rounded-xl btn-accent"
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
        </Link>
    );
}

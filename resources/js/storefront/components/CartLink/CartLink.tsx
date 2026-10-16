import { Link, usePage } from '@inertiajs/react';
import { cart } from '@/routes';
import type { SharedProps } from '@/shared/types';
import { Icon } from '@/shared/ui';

export default function CartLink() {
    const { count } = usePage<SharedProps>().props.cart;

    return (
        <Link
            href={cart()}
            aria-label={`Cart, ${count} ${count === 1 ? 'item' : 'items'}`}
            className="relative flex h-11 w-11 items-center justify-center rounded-control text-neutral-800 transition-colors hover:bg-neutral-100"
        >
            <Icon name="cart" size={24} />
            {count > 0 && (
                <span className="absolute top-0 right-0 flex h-5 min-w-5 items-center justify-center rounded-full bg-accent-600 px-1 text-xs font-semibold text-white">
                    {count}
                </span>
            )}
        </Link>
    );
}

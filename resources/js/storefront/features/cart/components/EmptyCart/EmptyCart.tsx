import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import { Icon } from '@/shared/ui';

export default function EmptyCart() {
    return (
        <div className="flex flex-col items-center gap-4 rounded-card border border-dashed border-neutral-300 bg-white px-6 py-16 text-center">
            <span className="flex size-16 items-center justify-center rounded-full bg-accent-50 text-accent-600">
                <Icon name="cart" size={28} />
            </span>
            <h2 className="text-lg font-bold text-neutral-900">
                Your cart is empty
            </h2>
            <p className="max-w-sm text-sm text-neutral-600">
                Browse our products and add your favourites to place an order.
            </p>
            <Link
                href={home()}
                className="inline-flex h-11 items-center rounded-control btn-accent px-6 text-sm font-semibold text-white"
            >
                Continue shopping
            </Link>
        </div>
    );
}

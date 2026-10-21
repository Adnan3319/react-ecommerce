import { Link, router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { cart, home } from '@/routes';
import { formatMoney } from '@/shared/lib';
import { useMiniCart } from '@/storefront/components/MiniCartProvider';
import Sheet from '@/storefront/components/Sheet';
import { Icon } from '@/storefront/components/ui';
import { CART_FORM_ANCHOR } from './constants';
import MiniCartLine from './MiniCartLine';
import type { MiniCartData } from './types';

/**
 * Right drawer (bottom sheet on mobile) with the visitor's cart. The `miniCart` prop is optional on the
 * server, so it is requested with a partial reload the first time the drawer opens on a page; every cart
 * change reloads it together with the other cart props.
 */
export default function MiniCartDrawer() {
    const { isOpen, close } = useMiniCart();
    const { miniCart } = usePage<{ miniCart?: MiniCartData }>().props;

    useEffect(() => {
        if (isOpen && miniCart === undefined) {
            router.reload({ only: ['miniCart'] });
        }
    }, [isOpen, miniCart]);

    const lines = miniCart?.lines ?? [];

    return (
        <Sheet
            open={isOpen}
            onClose={close}
            label="Your cart"
            placement="drawer"
        >
            <div className="flex max-h-[90dvh] flex-col sm:h-dvh sm:max-h-none">
                <div className="flex items-center justify-between border-b border-neutral-200 px-5 py-4">
                    <h2 className="text-lg font-bold text-neutral-900">
                        Your cart
                    </h2>
                    <button
                        type="button"
                        onClick={close}
                        aria-label="Close cart"
                        className="flex size-11 items-center justify-center rounded-control text-neutral-700 hover:bg-neutral-100"
                    >
                        <Icon name="close" size={20} />
                    </button>
                </div>

                {miniCart === undefined && (
                    <p
                        role="status"
                        className="px-5 py-10 text-center text-sm text-neutral-600"
                    >
                        Loading your cart…
                    </p>
                )}

                {miniCart !== undefined && lines.length === 0 && (
                    <div className="flex flex-col items-center gap-4 px-5 py-14 text-center">
                        <span className="flex size-14 items-center justify-center rounded-full bg-accent-50 text-accent-600">
                            <Icon name="cart" size={26} />
                        </span>
                        <p className="font-semibold text-neutral-900">
                            Your cart is empty
                        </p>
                        <Link
                            href={home()}
                            onClick={close}
                            className="inline-flex h-11 items-center rounded-control bg-accent-500 px-6 text-sm font-semibold text-white hover:bg-accent-600"
                        >
                            Continue shopping
                        </Link>
                    </div>
                )}

                {miniCart !== undefined && lines.length > 0 && (
                    <>
                        <ul className="min-h-0 flex-1 divide-y divide-neutral-100 overflow-y-auto px-5">
                            {lines.map((line) => (
                                <MiniCartLine
                                    key={`${line.variant_id}:${line.quantity}`}
                                    line={line}
                                    onNavigate={close}
                                />
                            ))}
                        </ul>
                        <div className="border-t border-neutral-200 px-5 py-4">
                            <p className="flex items-baseline justify-between">
                                <span className="text-sm text-neutral-600">
                                    Subtotal
                                </span>
                                <span
                                    data-testid="mini-cart-subtotal"
                                    className="text-xl font-bold text-neutral-900"
                                >
                                    {formatMoney(miniCart.subtotal)}
                                </span>
                            </p>
                            <p className="mt-1 text-xs text-neutral-500">
                                Delivery charge is added at checkout.
                            </p>
                            <div className="mt-4 grid grid-cols-2 gap-3">
                                <Link
                                    href={cart()}
                                    onClick={close}
                                    className="inline-flex h-11 items-center justify-center rounded-control border-2 border-accent-500 text-sm font-semibold text-accent-600 hover:bg-accent-50"
                                >
                                    View cart
                                </Link>
                                <Link
                                    href={`${cart().url}#${CART_FORM_ANCHOR}`}
                                    onClick={close}
                                    className="inline-flex h-11 items-center justify-center rounded-control bg-accent-500 text-sm font-semibold text-white hover:bg-accent-600"
                                >
                                    Checkout
                                </Link>
                            </div>
                        </div>
                    </>
                )}
            </div>
        </Sheet>
    );
}

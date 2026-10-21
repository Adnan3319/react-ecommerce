import { router } from '@inertiajs/react';
import { useState } from 'react';
import { store } from '@/routes/cart/items';
import { useMiniCart } from '@/storefront/components/MiniCartProvider';

/** Props a cart change reloads: the cart page data, the mini cart and the header count. */
export const CART_PROPS = ['lines', 'subtotal', 'cart', 'miniCart'];

/**
 * Adds `quantity` of one variant to the server cart and opens the mini cart with the new line;
 * `onAdded` lets a caller react to the success (such as closing a picker).
 */
export function useAddToCart(
    variantId: number | null,
    quantity: number,
    onAdded?: () => void,
) {
    const miniCart = useMiniCart();
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    function add(): void {
        if (variantId === null) {
            return;
        }

        router.post(
            store().url,
            { variant_id: variantId, quantity },
            {
                preserveScroll: true,
                preserveState: true,
                only: CART_PROPS,
                onStart: () => {
                    setProcessing(true);
                    setError(null);
                },
                onSuccess: () => {
                    onAdded?.();
                    miniCart.open();
                },
                onError: (errors) =>
                    setError(
                        errors.quantity ??
                            errors.variant ??
                            errors.cart ??
                            'Could not add to cart.',
                    ),
                onFinish: () => setProcessing(false),
            },
        );
    }

    return { add, processing, error };
}

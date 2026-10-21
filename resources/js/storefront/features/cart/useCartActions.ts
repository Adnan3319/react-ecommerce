import { router } from '@inertiajs/react';
import { destroy, update } from '@/routes/cart/items';
import { CART_PROPS } from '@/storefront/hooks/useAddToCart';

const visitOptions = {
    preserveScroll: true,
    preserveState: true,
    only: CART_PROPS,
} as const;

/** Changes to the lines of the cart page. Each one reloads only the cart props. */
export function useCartActions() {
    return {
        setQuantity: (
            variantId: number,
            quantity: number,
            onError: () => void,
        ) =>
            router.patch(
                update(variantId).url,
                { quantity },
                { ...visitOptions, onError },
            ),
        remove: (variantId: number) =>
            router.delete(destroy(variantId).url, visitOptions),
    };
}

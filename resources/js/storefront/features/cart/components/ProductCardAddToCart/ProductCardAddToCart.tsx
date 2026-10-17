import type { CartProduct } from '../../types';
import { AddToCartButton } from '../AddToCartButton';

type ProductCardAddToCartProps = {
    product: CartProduct;
};

/** The Add to Cart control every product card uses; keep card actions identical. */
export default function ProductCardAddToCart({
    product,
}: ProductCardAddToCartProps) {
    return (
        <AddToCartButton
            product={product}
            size="sm"
            fullWidth
            className="mt-auto"
        />
    );
}

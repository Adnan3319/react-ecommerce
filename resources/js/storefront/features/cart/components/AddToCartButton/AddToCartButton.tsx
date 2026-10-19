import { Button, Icon } from '@/storefront/components/ui';
import type { ButtonProps } from '@/storefront/components/ui';
import { useAddToCart } from '../../hooks/useAddToCart';
import type { CartProduct } from '../../types';

type AddToCartButtonProps = Pick<
    ButtonProps,
    'size' | 'fullWidth' | 'className'
> & {
    product: CartProduct;
    label?: string;
    /** Idle look; the added state is always the dark variant. */
    variant?: 'soft' | 'primary';
};

export default function AddToCartButton({
    product,
    label = 'Add to Cart',
    variant = 'primary',
    ...buttonProps
}: AddToCartButtonProps) {
    const { isAdded, add } = useAddToCart(product);

    return (
        <Button
            variant={isAdded ? 'secondary' : variant}
            onClick={add}
            {...buttonProps}
        >
            <Icon name={isAdded ? 'check' : 'cart'} size={18} />
            <span aria-live="polite">{isAdded ? 'Added' : label}</span>
        </Button>
    );
}

import { Button, Icon } from '@/shared/ui';
import type { ButtonProps } from '@/shared/ui';
import { useAddToCart } from '../../hooks/useAddToCart';
import type { CartProduct } from '../../types';

type AddToCartButtonProps = Pick<
    ButtonProps,
    'size' | 'fullWidth' | 'className'
> & {
    product: CartProduct;
    label?: string;
};

export default function AddToCartButton({
    product,
    label = 'Add to Cart',
    ...buttonProps
}: AddToCartButtonProps) {
    const { isAdded, add } = useAddToCart(product);

    return (
        <Button
            variant={isAdded ? 'secondary' : 'soft'}
            onClick={add}
            {...buttonProps}
        >
            <Icon name={isAdded ? 'check' : 'cart'} size={18} />
            <span aria-live="polite">{isAdded ? 'Added' : label}</span>
        </Button>
    );
}

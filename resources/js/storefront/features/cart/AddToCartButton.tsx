import { Button, Icon } from '@/storefront/components/ui';

type AddToCartButtonProps = {
    onAdd: () => void;
    processing: boolean;
    disabled: boolean;
    error: string | null;
};

export default function AddToCartButton({
    onAdd,
    processing,
    disabled,
    error,
}: AddToCartButtonProps) {
    return (
        <div className="flex flex-col gap-2">
            <Button
                type="button"
                variant="outline"
                size="lg"
                fullWidth
                disabled={disabled || processing}
                loading={processing}
                onClick={onAdd}
            >
                <Icon name="cart" size={18} />
                Add to Cart
            </Button>
            {error && (
                <p role="alert" className="text-sm text-red-700">
                    {error}
                </p>
            )}
        </div>
    );
}

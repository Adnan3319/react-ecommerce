import { Link } from '@inertiajs/react';
import { show } from '@/routes/products';
import { formatMoney } from '@/shared/lib';
import {
    Icon,
    Image,
    ImagePlaceholder,
    QuantityStepper,
} from '@/storefront/components/ui';
import type { CartLine } from './types';
import { useCartActions } from './useCartActions';
import { useDebouncedQuantity } from './useDebouncedQuantity';

const IMAGE_SIZE = 96;

type CartLineItemProps = {
    line: CartLine;
};

function notice(line: CartLine): string | null {
    if (line.unavailable) {
        return line.stock > 0 && line.quantity > line.stock
            ? `Only ${line.stock} left. Lower the quantity to include it in your order.`
            : 'No longer available. It is not included in your order.';
    }

    return line.price_changed
        ? `Price changed from ${formatMoney(line.previous_price)} to ${formatMoney(line.unit_price)} since you added it.`
        : null;
}

export default function CartLineItem({ line }: CartLineItemProps) {
    const { setQuantity, remove } = useCartActions();
    const [quantity, setLocalQuantity] = useDebouncedQuantity(
        line.quantity,
        (next, revert) => setQuantity(line.variant_id, next, revert),
    );
    const message = notice(line);

    return (
        <li className="py-5">
            <div className="flex gap-4">
                <Link
                    href={show(line.slug)}
                    aria-label={line.product_name}
                    className="size-24 shrink-0 overflow-hidden rounded-control bg-cream-50"
                >
                    {line.image_url ? (
                        <Image
                            src={line.image_url}
                            alt={line.product_name}
                            width={IMAGE_SIZE}
                            height={IMAGE_SIZE}
                            className="h-full w-full object-cover"
                        />
                    ) : (
                        <ImagePlaceholder className="h-full w-full" />
                    )}
                </Link>

                <div className="flex min-w-0 flex-1 flex-col gap-2">
                    <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0">
                            <Link
                                href={show(line.slug)}
                                className="line-clamp-2 font-semibold text-neutral-900 hover:text-accent-600"
                            >
                                {line.product_name}
                            </Link>
                            {line.variant_label && (
                                <p className="text-sm text-neutral-600">
                                    {line.variant_label}
                                </p>
                            )}
                        </div>
                        <p className="font-bold text-neutral-900">
                            {formatMoney(line.unit_price * quantity)}
                        </p>
                    </div>

                    <div className="mt-auto flex items-center justify-between gap-3">
                        <QuantityStepper
                            size="sm"
                            value={quantity}
                            min={1}
                            max={line.max_quantity}
                            onChange={setLocalQuantity}
                        />
                        <button
                            type="button"
                            onClick={() => remove(line.variant_id)}
                            className="inline-flex items-center gap-1 text-sm text-neutral-500 hover:text-red-600"
                        >
                            <Icon name="trash" size={16} />
                            Remove
                        </button>
                    </div>
                </div>
            </div>

            {message && (
                <p
                    role="alert"
                    className="mt-3 rounded-control bg-red-50 px-3 py-2 text-sm text-red-700"
                >
                    {message}
                </p>
            )}
        </li>
    );
}

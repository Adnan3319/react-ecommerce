import { Link } from '@inertiajs/react';
import { show } from '@/routes/products';
import { formatMoney } from '@/shared/lib';
import {
    Icon,
    Image,
    ImagePlaceholder,
    QuantityStepper,
} from '@/storefront/components/ui';
import { notice } from './CartLineItem';
import type { CartLine } from './types';
import { useCartActions } from './useCartActions';
import { useDebouncedQuantity } from './useDebouncedQuantity';

const IMAGE_SIZE = 64;

type MiniCartLineProps = {
    line: CartLine;
    onNavigate: () => void;
};

/** One compact line of the mini cart drawer; quantity and removal use the same cart routes as the cart page. */
export default function MiniCartLine({ line, onNavigate }: MiniCartLineProps) {
    const { setQuantity, remove } = useCartActions();
    const [quantity, setLocalQuantity] = useDebouncedQuantity(
        line.quantity,
        (next, revert) => setQuantity(line.variant_id, next, revert),
    );
    const message = notice(line);

    return (
        <li className="py-4">
            <div className="flex gap-3">
                <Link
                    href={show(line.slug)}
                    onClick={onNavigate}
                    aria-label={line.product_name}
                    className="size-16 shrink-0 overflow-hidden rounded-control bg-cream-50"
                >
                    {line.image_url ? (
                        <Image
                            src={line.image_url}
                            alt=""
                            width={IMAGE_SIZE}
                            height={IMAGE_SIZE}
                            className="h-full w-full object-cover"
                        />
                    ) : (
                        <ImagePlaceholder className="h-full w-full" />
                    )}
                </Link>

                <div className="flex min-w-0 flex-1 flex-col gap-2">
                    <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0">
                            <Link
                                href={show(line.slug)}
                                onClick={onNavigate}
                                className="line-clamp-2 text-sm font-semibold text-neutral-900 hover:text-accent-600"
                            >
                                {line.product_name}
                            </Link>
                            {line.variant_label && (
                                <p className="text-xs text-neutral-600">
                                    {line.variant_label}
                                </p>
                            )}
                        </div>
                        <p className="text-sm font-bold text-neutral-900">
                            {formatMoney(line.unit_price * quantity)}
                        </p>
                    </div>

                    <div className="flex items-center justify-between gap-2">
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
                            aria-label={`Remove ${line.product_name}`}
                            className="flex size-9 items-center justify-center rounded-control text-neutral-500 hover:bg-neutral-100 hover:text-red-600"
                        >
                            <Icon name="trash" size={16} />
                        </button>
                    </div>
                </div>
            </div>

            {message && (
                <p
                    role="alert"
                    className="mt-2 rounded-control bg-red-50 px-3 py-2 text-xs text-red-700"
                >
                    {message}
                </p>
            )}
        </li>
    );
}

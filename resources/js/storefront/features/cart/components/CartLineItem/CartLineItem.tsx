import { Link } from '@inertiajs/react';
import { show } from '@/routes/products';
import { formatMoney } from '@/shared/lib';
import { Icon, Image, ImagePlaceholder, QuantityStepper } from '@/storefront/components/ui';
import { CART_MIN_QUANTITY } from '../../constants';
import type { CartLine } from '../../types';

type CartLineItemProps = {
    line: CartLine;
    onQuantityChange: (lineId: number, quantity: number) => void;
    onRemove: (lineId: number) => void;
};

const IMAGE_SIZE = 96;

export default function CartLineItem({
    line,
    onQuantityChange,
    onRemove,
}: CartLineItemProps) {
    return (
        <li className="flex gap-4 py-5">
            <Link
                href={show(line.slug)}
                aria-label={line.name}
                className="size-24 shrink-0 overflow-hidden rounded-control bg-cream-50"
            >
                {line.image ? (
                    <Image
                        src={line.image}
                        alt={line.name}
                        width={IMAGE_SIZE}
                        height={IMAGE_SIZE}
                        className="h-full w-full object-cover"
                    />
                ) : (
                    <ImagePlaceholder className="h-full w-full" />
                )}
            </Link>

            <div className="flex min-w-0 flex-1 flex-col justify-between gap-3">
                <div className="flex items-start justify-between gap-3">
                    <h3 className="line-clamp-2 text-sm font-semibold text-neutral-900 sm:text-base">
                        <Link href={show(line.slug)}>{line.name}</Link>
                    </h3>
                    <button
                        type="button"
                        aria-label={`Remove ${line.name}`}
                        onClick={() => onRemove(line.id)}
                        className="flex size-9 shrink-0 items-center justify-center rounded-control text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-red-600"
                    >
                        <Icon name="trash" size={18} />
                    </button>
                </div>

                <div className="flex items-end justify-between gap-3">
                    <QuantityStepper
                        size="sm"
                        value={line.quantity}
                        min={CART_MIN_QUANTITY}
                        max={line.maxQuantity}
                        onChange={(quantity) =>
                            onQuantityChange(line.id, quantity)
                        }
                    />
                    <p className="text-right">
                        <span className="block text-base font-bold text-neutral-900">
                            {formatMoney(line.unitPrice * line.quantity)}
                        </span>
                        {line.quantity > 1 && (
                            <span className="text-xs text-neutral-500">
                                {formatMoney(line.unitPrice)} each
                            </span>
                        )}
                    </p>
                </div>
            </div>
        </li>
    );
}

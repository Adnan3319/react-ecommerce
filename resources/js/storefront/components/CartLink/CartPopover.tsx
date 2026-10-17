import { Link } from '@inertiajs/react';
import { cart } from '@/routes';
import { formatMoney } from '@/shared/lib';
import { Button, Icon, Image, ImagePlaceholder } from '@/shared/ui';
import type { CartLine } from '@/storefront/features/cart';

type CartPopoverProps = {
    lines: CartLine[];
    subtotal: number;
    onRemove: (lineId: number) => void;
    onClear: () => void;
    onNavigate: () => void;
};

const THUMB_SIZE = 56;

export default function CartPopover({
    lines,
    subtotal,
    onRemove,
    onClear,
    onNavigate,
}: CartPopoverProps) {
    return (
        <div
            id="mini-cart"
            role="dialog"
            aria-label="Cart preview"
            className="absolute top-full right-0 z-50 w-[min(22rem,calc(100vw-2rem))] pt-2"
        >
            <div className="rounded-card border border-neutral-200 bg-white p-4 shadow-xl">
                {lines.length === 0 ? (
                    <p className="py-6 text-center text-sm text-neutral-600">
                        Your cart is empty.
                    </p>
                ) : (
                    <ul className="max-h-72 divide-y divide-neutral-100 overflow-y-auto">
                        {lines.map((line) => (
                            <li key={line.id} className="flex gap-3 py-3">
                                <div className="size-14 shrink-0 overflow-hidden rounded-control bg-cream-50">
                                    {line.image ? (
                                        <Image
                                            src={line.image}
                                            alt={line.name}
                                            width={THUMB_SIZE}
                                            height={THUMB_SIZE}
                                            className="h-full w-full object-cover"
                                        />
                                    ) : (
                                        <ImagePlaceholder className="h-full w-full" />
                                    )}
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="line-clamp-2 text-sm font-medium text-neutral-900">
                                        {line.name}
                                    </p>
                                    <p className="mt-0.5 text-xs text-neutral-600">
                                        {line.quantity} &times;{' '}
                                        {formatMoney(line.unitPrice)}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    aria-label={`Remove ${line.name}`}
                                    onClick={() => onRemove(line.id)}
                                    className="flex size-8 shrink-0 items-center justify-center self-center rounded-control text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-red-600"
                                >
                                    <Icon name="trash" size={16} />
                                </button>
                            </li>
                        ))}
                    </ul>
                )}

                {lines.length > 0 && (
                    <div className="mt-2 flex items-center justify-between border-t border-neutral-200 pt-3 text-sm">
                        <span className="text-neutral-600">Subtotal</span>
                        <span className="font-bold text-neutral-900">
                            {formatMoney(subtotal)}
                        </span>
                    </div>
                )}

                <div className="mt-3 flex gap-2">
                    {lines.length > 0 && (
                        <Button
                            variant="outline"
                            onClick={onClear}
                            className="flex-1"
                        >
                            Clear
                        </Button>
                    )}
                    <Link
                        href={cart()}
                        onClick={onNavigate}
                        className="flex h-11 flex-1 items-center justify-center rounded-control bg-accent-600 text-base font-semibold text-white transition-colors hover:bg-accent-700"
                    >
                        View cart
                    </Link>
                </div>
            </div>
        </div>
    );
}

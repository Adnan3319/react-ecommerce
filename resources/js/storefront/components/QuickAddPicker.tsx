import { Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { options as optionsRoute, show } from '@/routes/products';
import { formatMoney } from '@/shared/lib';
import { Button, Icon, Image } from '@/storefront/components/ui';
import type { QuickAddOptions } from '@/storefront/features/catalog/types';
import { useAddToCart } from '@/storefront/hooks/useAddToCart';
import Sheet from './Sheet';
import VariantPicker from './VariantPicker';
import { useVariantSelection } from './useVariantSelection';

type QuickAddPickerProps = {
    slug: string;
    open: boolean;
    onClose: () => void;
};

type LoadState =
    | { status: 'idle' }
    | { status: 'loading' }
    | { status: 'error' }
    | { status: 'ready'; options: QuickAddOptions };

const THUMB_SIZE = 80;

/** Modal on desktop, bottom sheet on mobile. Loads the product options on first open only. */
export default function QuickAddPicker({
    slug,
    open,
    onClose,
}: QuickAddPickerProps) {
    const [state, setState] = useState<LoadState>({ status: 'idle' });
    const [attempt, setAttempt] = useState(0);
    const startedAttempt = useRef(-1);
    const mounted = useRef(false);

    useEffect(() => {
        mounted.current = true;

        return () => {
            mounted.current = false;
        };
    }, []);

    // The options are requested once, on the first open (or again after "Try again").
    useEffect(() => {
        if (!open || startedAttempt.current === attempt) {
            return;
        }

        startedAttempt.current = attempt;
        setState({ status: 'loading' });

        fetch(optionsRoute(slug).url, {
            headers: { Accept: 'application/json' },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(
                        `Options request failed: ${response.status}`,
                    );
                }

                return response.json() as Promise<{ data: QuickAddOptions }>;
            })
            .then((body) => {
                if (mounted.current) {
                    setState({ status: 'ready', options: body.data });
                }
            })
            .catch(() => {
                if (mounted.current) {
                    setState({ status: 'error' });
                }
            });
    }, [open, slug, attempt]);

    function retry(): void {
        setAttempt((current) => current + 1);
    }
    return (
        <Sheet open={open} onClose={onClose} label="Choose options">
            <div className="flex flex-col gap-5 p-5">
                {state.status === 'ready' && (
                    <PickerContent options={state.options} onClose={onClose} />
                )}
                {state.status === 'error' && (
                    <div className="flex flex-col items-start gap-3">
                        <p role="alert" className="text-sm text-red-700">
                            Could not load the options.
                        </p>
                        <Button variant="outline" size="sm" onClick={retry}>
                            Try again
                        </Button>
                    </div>
                )}
                {(state.status === 'loading' || state.status === 'idle') && (
                    <p
                        role="status"
                        className="py-10 text-center text-sm text-neutral-600"
                    >
                        Loading options…
                    </p>
                )}
            </div>
        </Sheet>
    );
}

type PickerContentProps = {
    options: QuickAddOptions;
    onClose: () => void;
};

function PickerContent({ options, onClose }: PickerContentProps) {
    const { selected, variant, isAvailable, select } = useVariantSelection(
        options.heads,
        options.variants,
    );
    const cart = useAddToCart(variant?.id ?? null, 1, onClose);
    const outOfStock = !variant || variant.stock <= 0;
    const thumb = variant?.thumb ?? options.thumb;

    return (
        <>
            <div className="flex items-start gap-4">
                {thumb && (
                    <Image
                        src={thumb}
                        alt={options.name}
                        width={THUMB_SIZE}
                        height={THUMB_SIZE}
                        className="size-20 shrink-0 rounded-full object-cover"
                    />
                )}
                <div className="min-w-0 flex-1">
                    <h2 className="text-lg font-bold text-neutral-900">
                        {options.name}
                    </h2>
                    {variant && (
                        <p className="mt-1 flex items-baseline gap-2">
                            <span className="text-lg font-bold text-neutral-900">
                                {formatMoney(variant.effective_price)}
                            </span>
                            {variant.sale_price !== null && (
                                <s className="text-sm text-neutral-500">
                                    {formatMoney(variant.price)}
                                </s>
                            )}
                        </p>
                    )}
                    <p
                        className={
                            outOfStock
                                ? 'text-sm text-red-700'
                                : 'text-sm text-green-700'
                        }
                    >
                        {outOfStock
                            ? 'Out of stock'
                            : variant.stock_state === 'low'
                              ? `In stock · ${variant.stock} left`
                              : 'In stock'}
                    </p>
                </div>
                <button
                    type="button"
                    aria-label="Close"
                    onClick={onClose}
                    className="flex size-9 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-neutral-700 hover:bg-neutral-200"
                >
                    <Icon name="close" size={16} />
                </button>
            </div>

            <VariantPicker
                heads={options.heads}
                selected={selected}
                isAvailable={isAvailable}
                onSelect={select}
            />

            <div className="flex flex-col gap-2">
                <Button
                    size="lg"
                    fullWidth
                    disabled={outOfStock}
                    loading={cart.processing}
                    onClick={cart.add}
                >
                    Add to Cart
                </Button>
                {cart.error && (
                    <p role="alert" className="text-sm text-red-700">
                        {cart.error}
                    </p>
                )}
                <Link
                    href={show(options.slug)}
                    className="text-center text-sm text-neutral-600 hover:text-accent-600"
                >
                    View full details →
                </Link>
            </div>
        </>
    );
}

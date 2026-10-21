import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { show } from '@/routes/products';
import { cn, formatMoney } from '@/shared/lib';
import AddedToast from '@/storefront/components/AddedToast';
import { Button, Image, ImagePlaceholder } from '@/storefront/components/ui';
import { useAddToCart } from '@/storefront/hooks/useAddToCart';
import QuickAddPicker from './QuickAddPicker';
import type { ProductCardData } from './types';

type ProductCardProps = {
    product: ProductCardData;
    /** Loads the image right away; used for the first visible row. */
    eager?: boolean;
};

const IMAGE_SIZE = 400;
const IMAGE_SIZES = '(min-width: 1024px) 25vw, 50vw';

const LINK_BUTTON =
    'inline-flex h-10 items-center justify-center rounded-control px-3 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600';

/**
 * Product tile for every storefront list. Order Now opens the product page (where the order form
 * is); Add to Cart adds a simple product directly and opens the quick picker for one with options.
 */
export default function ProductCard({
    product,
    eager = false,
}: ProductCardProps) {
    const [pickerOpen, setPickerOpen] = useState(false);
    const [toastVisible, setToastVisible] = useState(false);
    const cart = useAddToCart(product.default_variant_id, 1, () =>
        setToastVisible(true),
    );
    const href = show(product.slug);
    const current = product.sale_price ?? product.price;

    return (
        <article className="flex h-full flex-col overflow-hidden rounded-card bg-white shadow-card ring-1 ring-neutral-200">
            <div className="relative">
                <Link
                    href={href}
                    tabIndex={-1}
                    className="block aspect-square overflow-hidden bg-cream-50"
                >
                    {product.thumb ? (
                        <Image
                            src={product.thumb}
                            srcSet={product.srcset ?? undefined}
                            sizes={IMAGE_SIZES}
                            alt={product.name}
                            width={IMAGE_SIZE}
                            height={IMAGE_SIZE}
                            eager={eager}
                            className="h-full w-full object-cover"
                        />
                    ) : (
                        <ImagePlaceholder className="h-full w-full" />
                    )}
                </Link>
                <div className="pointer-events-none absolute top-2 left-2 flex flex-col items-start gap-1">
                    {product.discount_percent !== null && (
                        <span className="rounded-full bg-accent-600 px-2 py-0.5 text-xs font-semibold text-white">
                            -{product.discount_percent}%
                        </span>
                    )}
                    {product.is_new && (
                        <span className="rounded-full bg-neutral-900 px-2 py-0.5 text-xs font-semibold text-white">
                            New
                        </span>
                    )}
                </div>
            </div>

            <div className="flex flex-1 flex-col gap-2 p-3">
                <h3 className="line-clamp-2 min-h-10 text-sm font-semibold text-neutral-900">
                    <Link href={href} className="hover:text-accent-600">
                        {product.name}
                    </Link>
                </h3>
                {/* Height is reserved so cards with and without options stay aligned. */}
                <p className="min-h-4 text-xs text-neutral-500">
                    {product.options_summary}
                </p>
                <p className="flex items-baseline gap-2">
                    {current !== null && (
                        <span className="text-base font-bold text-neutral-900">
                            {formatMoney(current)}
                        </span>
                    )}
                    {product.sale_price !== null && product.price !== null && (
                        <s className="text-xs text-neutral-500">
                            {formatMoney(product.price)}
                        </s>
                    )}
                </p>
                {!product.in_stock && (
                    <p className="text-xs font-medium text-red-700">
                        Out of stock
                    </p>
                )}

                <div className="mt-auto flex gap-2 pt-1">
                    {product.in_stock ? (
                        <>
                            <Link
                                href={href}
                                className={cn(
                                    LINK_BUTTON,
                                    'flex-1 bg-neutral-900 text-white hover:bg-neutral-800',
                                )}
                            >
                                Order Now
                            </Link>
                            <Button
                                variant="outline"
                                size="sm"
                                className="h-10"
                                loading={cart.processing}
                                aria-label={`Add ${product.name} to cart`}
                                onClick={
                                    product.has_variants
                                        ? () => setPickerOpen(true)
                                        : cart.add
                                }
                            >
                                + Cart
                            </Button>
                        </>
                    ) : (
                        <Link
                            href={href}
                            className={cn(
                                LINK_BUTTON,
                                'flex-1 bg-neutral-100 text-neutral-700 hover:bg-neutral-200',
                            )}
                        >
                            View
                        </Link>
                    )}
                </div>
                {cart.error && (
                    <p role="alert" className="text-xs text-red-700">
                        {cart.error}
                    </p>
                )}
            </div>

            {product.in_stock && product.has_variants && (
                <QuickAddPicker
                    slug={product.slug}
                    open={pickerOpen}
                    onClose={() => setPickerOpen(false)}
                    onAdded={() => setToastVisible(true)}
                />
            )}
            {toastVisible && (
                <AddedToast onDismiss={() => setToastVisible(false)} />
            )}
        </article>
    );
}

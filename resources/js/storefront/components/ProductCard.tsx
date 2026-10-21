import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { show } from '@/routes/products';
import { cn, formatMoney } from '@/shared/lib';
import { Icon, Image, ImagePlaceholder } from '@/storefront/components/ui';
import type { ProductCardData } from '@/storefront/features/catalog/types';
import { useAddToCart } from '@/storefront/hooks/useAddToCart';
import QuickAddPicker from './QuickAddPicker';

type ProductCardProps = {
    product: ProductCardData;
    /** Loads the image right away; used for the first visible row. */
    eager?: boolean;
};

const IMAGE_SIZE = 400;
const IMAGE_SIZES = '(min-width: 1280px) 20vw, (min-width: 768px) 25vw, 45vw';

const ACTION_BASE =
    'inline-flex h-11 items-center justify-center rounded-control text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600';

/**
 * The one product tile of every storefront list (Home rails, category, search). Order Now opens the
 * product page (where the order form is); the cart button adds a simple product directly and opens the
 * quick picker for one with options. Either way the mini cart opens with the new line.
 */
export default function ProductCard({
    product,
    eager = false,
}: ProductCardProps) {
    const [pickerOpen, setPickerOpen] = useState(false);
    const cart = useAddToCart(product.default_variant_id, 1);
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
                        <span className="rounded-full bg-accent-600 px-2.5 py-0.5 text-xs font-semibold text-white">
                            -{product.discount_percent}%
                        </span>
                    )}
                    {product.is_new && (
                        <span className="rounded-full bg-neutral-900 px-2.5 py-0.5 text-xs font-semibold text-white">
                            New
                        </span>
                    )}
                </div>
            </div>

            <div className="flex flex-1 flex-col gap-1.5 p-3 sm:p-4">
                <h3 className="line-clamp-2 min-h-[2.75rem] text-[15px] leading-snug font-semibold text-neutral-900">
                    <Link href={href} className="hover:text-accent-600">
                        {product.name}
                    </Link>
                </h3>
                {/* Height is reserved so cards with and without options stay aligned. */}
                <p className="min-h-5 truncate text-[13px] text-neutral-500">
                    {product.options_summary}
                </p>
                <p className="flex flex-wrap items-baseline gap-x-2">
                    {current !== null && (
                        <span className="text-xl font-bold text-neutral-900">
                            {formatMoney(current)}
                        </span>
                    )}
                    {product.sale_price !== null && product.price !== null && (
                        <s className="text-sm text-neutral-500">
                            {formatMoney(product.price)}
                        </s>
                    )}
                </p>

                <div className="mt-auto flex gap-2 pt-2">
                    {product.in_stock ? (
                        <>
                            <Link
                                href={href}
                                className={cn(
                                    ACTION_BASE,
                                    'flex-1 btn-accent',
                                )}
                            >
                                Order Now
                            </Link>
                            <button
                                type="button"
                                disabled={cart.processing}
                                aria-busy={cart.processing || undefined}
                                aria-label={`Add ${product.name} to cart`}
                                onClick={
                                    product.has_variants
                                        ? () => setPickerOpen(true)
                                        : cart.add
                                }
                                className={cn(
                                    ACTION_BASE,
                                    'size-11 shrink-0 btn-accent disabled:opacity-60',
                                )}
                            >
                                <Icon name="cart" size={20} />
                            </button>
                        </>
                    ) : (
                        <Link
                            href={href}
                            className={cn(
                                ACTION_BASE,
                                'flex-1 bg-neutral-100 text-neutral-600 hover:bg-neutral-200',
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
                />
            )}
        </article>
    );
}

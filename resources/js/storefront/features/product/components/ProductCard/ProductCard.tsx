import { Link } from '@inertiajs/react';
import { show } from '@/routes/products';
import { formatMoney } from '@/shared/lib';
import { Button, Icon, Image, ImagePlaceholder } from '@/shared/ui';
import type { ProductSummary } from '../../types';
import { RatingStars } from '../RatingStars';

type ProductCardProps = {
    product: ProductSummary;
};

const IMAGE_SIZE = 400;

export default function ProductCard({ product }: ProductCardProps) {
    const { slug, name, price, comparePrice, rating, reviewCount, image } =
        product;
    const hasDiscount = comparePrice !== null && comparePrice > price;

    return (
        <article className="flex h-full flex-col overflow-hidden rounded-card border border-neutral-200 bg-white shadow-card">
            <Link
                href={show(slug)}
                aria-label={name}
                className="block aspect-square overflow-hidden bg-cream-50"
            >
                {image ? (
                    <Image
                        src={image}
                        alt={name}
                        width={IMAGE_SIZE}
                        height={IMAGE_SIZE}
                        className="h-full w-full object-cover"
                    />
                ) : (
                    <ImagePlaceholder className="h-full w-full" />
                )}
            </Link>

            <div className="flex flex-1 flex-col gap-2 p-3">
                <h3 className="line-clamp-2 text-sm font-semibold text-neutral-900">
                    <Link href={show(slug)} className="hover:text-accent-600">
                        {name}
                    </Link>
                </h3>
                {rating !== null && (
                    <RatingStars rating={rating} reviewCount={reviewCount} />
                )}
                <p className="flex items-baseline gap-2">
                    <span className="text-base font-bold text-neutral-900">
                        {formatMoney(price)}
                    </span>
                    {hasDiscount && (
                        <span className="text-xs text-neutral-500 line-through">
                            {formatMoney(comparePrice)}
                        </span>
                    )}
                </p>
                <Button size="sm" fullWidth className="mt-auto">
                    <Icon name="cart" size={16} />
                    Add to Cart
                </Button>
            </div>
        </article>
    );
}

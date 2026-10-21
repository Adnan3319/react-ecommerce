import { Link } from '@inertiajs/react';
import { Image } from '@/storefront/components/ui';
import type { RouteDefinition } from '@/wayfinder';
import CategoryIcon from './CategoryIcon';

type CategoryTileProps = {
    slug: string;
    label: string;
    image: string | null;
    href: RouteDefinition<'get'>;
};

const IMAGE_SIZE = 128;
const ICON_SIZE = 40;

/** Small round-image tile for the category strip shown when the store has many root categories. */
export default function CategoryTile({
    slug,
    label,
    image,
    href,
}: CategoryTileProps) {
    return (
        <Link
            href={href}
            className="flex w-24 flex-col items-center gap-2 text-center focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600"
        >
            <span className="flex size-16 items-center justify-center overflow-hidden rounded-full bg-cream-50 ring-1 ring-neutral-200">
                {image ? (
                    <Image
                        src={image}
                        alt=""
                        width={IMAGE_SIZE}
                        height={IMAGE_SIZE}
                        className="size-full object-cover"
                    />
                ) : (
                    <CategoryIcon
                        slug={slug}
                        size={ICON_SIZE}
                        className="size-9"
                    />
                )}
            </span>
            <span className="line-clamp-2 text-xs font-semibold text-neutral-900">
                {label}
            </span>
        </Link>
    );
}

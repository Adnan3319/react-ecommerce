import { useState } from 'react';
import type { ReactNode } from 'react';
import { cn } from '@/shared/lib';
import { Image, ImagePlaceholder } from '@/storefront/components/ui';
import type { PageImage } from './types';

type ProductGalleryProps = {
    name: string;
    images: PageImage[];
    /** Image shown first (the picked variant's image); the shopper can still pick any thumbnail. */
    initialImageId: number | null;
    /** Overlay in the corner of the main image, e.g. the discount badge. */
    badge?: ReactNode;
};

const MAIN_SIZE = 1200;
const THUMB_SIZE = 400;
const MAIN_SIZES = '(min-width: 1024px) 50vw, 100vw';

/**
 * Main image plus thumbnails. The first image of the product is the LCP image (eager, high priority);
 * the thumbnails load lazily. Remount it (`key`) to jump to another image when the variant changes.
 */
export default function ProductGallery({
    name,
    images,
    initialImageId,
    badge,
}: ProductGalleryProps) {
    const [selectedId, setSelectedId] = useState(
        initialImageId ?? images[0]?.id ?? null,
    );
    const active = images.find((image) => image.id === selectedId) ?? images[0];

    return (
        <div className="flex flex-col gap-4">
            <div className="relative flex aspect-square items-center justify-center overflow-hidden rounded-[28px] bg-white shadow-[0_8px_30px_rgba(15,23,42,0.06)]">
                {active?.url ? (
                    <Image
                        src={active.url}
                        srcSet={active.srcset ?? undefined}
                        sizes={MAIN_SIZES}
                        alt={name}
                        width={MAIN_SIZE}
                        height={MAIN_SIZE}
                        priority={active.id === images[0]?.id}
                        className="h-full w-full object-cover"
                    />
                ) : (
                    <ImagePlaceholder className="h-full w-full" />
                )}
                {badge && <div className="absolute start-4 top-4">{badge}</div>}
            </div>

            {images.length > 1 && (
                <ul className="grid grid-cols-4 gap-3">
                    {images.map((image, index) => (
                        <li key={image.id}>
                            <button
                                type="button"
                                onClick={() => setSelectedId(image.id)}
                                aria-label={`Show image ${index + 1} of ${images.length}`}
                                aria-pressed={image.id === active?.id}
                                className={cn(
                                    'flex aspect-square w-full items-center justify-center overflow-hidden rounded-2xl bg-white p-1.5 ring-2 transition',
                                    image.id === active?.id
                                        ? 'ring-accent-600'
                                        : 'ring-transparent hover:ring-neutral-300',
                                )}
                            >
                                {image.thumb_url && (
                                    <Image
                                        src={image.thumb_url}
                                        alt=""
                                        width={THUMB_SIZE}
                                        height={THUMB_SIZE}
                                        className="h-full w-full object-cover"
                                    />
                                )}
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

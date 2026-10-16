import { useState } from 'react';
import { cn } from '@/shared/lib';
import { Image, ImagePlaceholder } from '@/shared/ui';

type ProductGalleryProps = {
    name: string;
    images: string[];
};

const MAIN_SIZE = 800;
const THUMB_SIZE = 120;

export default function ProductGallery({ name, images }: ProductGalleryProps) {
    const [selected, setSelected] = useState(0);
    const activeImage = images[selected] ?? images[0];

    if (!activeImage) {
        return (
            <ImagePlaceholder className="aspect-square w-full rounded-card border border-neutral-200" />
        );
    }

    return (
        <div className="flex flex-col gap-3">
            <div className="flex aspect-square items-center justify-center overflow-hidden rounded-card border border-neutral-200 bg-white">
                <Image
                    src={activeImage}
                    alt={name}
                    width={MAIN_SIZE}
                    height={MAIN_SIZE}
                    priority
                    className="h-full w-full object-contain"
                />
            </div>

            {images.length > 1 && (
                <ul className="flex gap-2 overflow-x-auto">
                    {images.map((src, index) => (
                        <li key={src} className="shrink-0">
                            <button
                                type="button"
                                onClick={() => setSelected(index)}
                                aria-label={`Show image ${index + 1} of ${images.length}`}
                                aria-pressed={index === selected}
                                className={cn(
                                    'block overflow-hidden rounded-control border-2 bg-white',
                                    index === selected
                                        ? 'border-accent-600'
                                        : 'border-neutral-200 hover:border-accent-500',
                                )}
                            >
                                <Image
                                    src={src}
                                    alt=""
                                    width={THUMB_SIZE}
                                    height={THUMB_SIZE}
                                    className="h-16 w-16 object-cover sm:h-20 sm:w-20"
                                />
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

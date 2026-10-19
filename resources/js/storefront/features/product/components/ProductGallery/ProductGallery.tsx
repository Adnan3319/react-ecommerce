import { useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { cn } from '@/shared/lib';
import { Image, ImagePlaceholder } from '@/shared/ui';

type ProductGalleryProps = {
    name: string;
    images: string[];
    /** Rendered to the right of the main image (title, price, buttons). */
    info?: ReactNode;
};

const MAIN_SIZE = 800;
const THUMB_SIZE = 160;
const SCROLL_STEP = 240;

function Chevron({ direction }: { direction: 'left' | 'right' }) {
    return (
        <svg
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2.5"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
        >
            <path d={direction === 'left' ? 'M15 6l-6 6 6 6' : 'M9 6l6 6-6 6'} />
        </svg>
    );
}

export default function ProductGallery({
    name,
    images,
    info,
}: ProductGalleryProps) {
    const [selected, setSelected] = useState(0);
    const listRef = useRef<HTMLUListElement>(null);
    const activeImage = images[selected] ?? images[0];

    function scrollList(direction: 1 | -1) {
        listRef.current?.scrollBy({
            left: direction * SCROLL_STEP,
            behavior: 'smooth',
        });
    }

    return (
        <div className="flex flex-col gap-8">
            {/* Top row: main image + product info */}
            <div
                className={cn(
                    'grid items-center gap-8',
                    info ? 'sm:grid-cols-2' : 'grid-cols-1',
                )}
            >
                {activeImage ? (
                    <div className="group relative flex aspect-square items-center justify-center overflow-hidden rounded-[32px] bg-white/70 p-6 shadow-[0_8px_30px_rgba(15,23,42,0.06)] ring-1 ring-white/80 backdrop-blur-md">
                        <Image
                            src={activeImage}
                            alt={name}
                            width={MAIN_SIZE}
                            height={MAIN_SIZE}
                            priority
                            className="h-full w-full object-contain transition-transform duration-500 group-hover:scale-105"
                        />
                    </div>
                ) : (
                    <ImagePlaceholder className="aspect-square w-full rounded-[32px] bg-white/70 shadow-[0_8px_30px_rgba(15,23,42,0.06)]" />
                )}

                {info}
            </div>

            {/* Thumbnails with prev / next arrows */}
            {images.length > 1 && (
                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        onClick={() => scrollList(-1)}
                        aria-label="Previous images"
                        className="shrink-0 rounded-full p-1.5 text-neutral-500 transition hover:bg-white/70 hover:text-neutral-900"
                    >
                        <Chevron direction="left" />
                    </button>

                    <ul
                        ref={listRef}
                        className="flex flex-1 gap-3 overflow-x-auto py-1 scrollbar-none"
                    >
                        {images.map((src, index) => (
                            <li key={src} className="shrink-0">
                                <button
                                    type="button"
                                    onClick={() => setSelected(index)}
                                    aria-label={`Show image ${index + 1} of ${images.length}`}
                                    aria-pressed={index === selected}
                                    className={cn(
                                        'flex h-24 w-20 items-center justify-center overflow-hidden rounded-2xl p-2 transition-all duration-200 sm:h-28 sm:w-24',
                                        index === selected
                                            ? 'bg-sky-200 shadow-md'
                                            : 'bg-neutral-200/60 opacity-80 hover:bg-neutral-200 hover:opacity-100',
                                    )}
                                >
                                    <Image
                                        src={src}
                                        alt=""
                                        width={THUMB_SIZE}
                                        height={THUMB_SIZE}
                                        className="h-full w-full object-contain"
                                    />
                                </button>
                            </li>
                        ))}
                    </ul>

                    <button
                        type="button"
                        onClick={() => scrollList(1)}
                        aria-label="Next images"
                        className="shrink-0 rounded-full p-1.5 text-neutral-500 transition hover:bg-white/70 hover:text-neutral-900"
                    >
                        <Chevron direction="right" />
                    </button>
                </div>
            )}
        </div>
    );
}

import { useEffect, useRef, useState } from 'react';
import type { ComponentProps, ReactNode } from 'react';
import { Icon, SectionHeader } from '@/storefront/components/ui';
import type { ProductSummary } from '../../types';
import { ProductCard } from '../ProductCard';

type ProductRailProps = Pick<
    ComponentProps<typeof SectionHeader>,
    'title' | 'subtitle' | 'viewAll'
> & {
    products: ProductSummary[];
    renderAction?: (product: ProductSummary) => ReactNode;
};

const ARROW_CLASS =
    'flex size-9 items-center justify-center rounded-full border border-neutral-300 bg-white text-neutral-800 shadow-card transition-colors hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-white';

/** Product slider: five cards per view on desktop, paged with the header arrows. */
export default function ProductRail({
    title,
    subtitle,
    viewAll,
    products,
    renderAction,
}: ProductRailProps) {
    const trackRef = useRef<HTMLUListElement>(null);
    const [canPrev, setCanPrev] = useState(false);
    const [canNext, setCanNext] = useState(false);

    useEffect(() => {
        const track = trackRef.current;

        if (!track) {
            return;
        }

        function syncArrows(): void {
            if (!track) {
                return;
            }

            setCanPrev(track.scrollLeft > 1);
            setCanNext(
                track.scrollLeft + track.clientWidth < track.scrollWidth - 1,
            );
        }

        syncArrows();
        track.addEventListener('scroll', syncArrows, { passive: true });
        const observer = new ResizeObserver(syncArrows);
        observer.observe(track);

        return () => {
            track.removeEventListener('scroll', syncArrows);
            observer.disconnect();
        };
    }, [products.length]);

    function slide(direction: -1 | 1): void {
        const track = trackRef.current;

        track?.scrollBy({
            left: direction * track.clientWidth,
            behavior: 'smooth',
        });
    }

    return (
        <section className="mx-auto max-w-7xl px-4 pt-12 sm:px-6">
            <SectionHeader
                title={title}
                subtitle={subtitle}
                viewAll={viewAll}
                action={
                    <div className="ml-auto flex gap-2">
                        <button
                            type="button"
                            aria-label={`Previous ${title} products`}
                            disabled={!canPrev}
                            onClick={() => slide(-1)}
                            className={ARROW_CLASS}
                        >
                            <Icon
                                name="arrowRight"
                                size={16}
                                className="rotate-180"
                            />
                        </button>
                        <button
                            type="button"
                            aria-label={`Next ${title} products`}
                            disabled={!canNext}
                            onClick={() => slide(1)}
                            className={ARROW_CLASS}
                        >
                            <Icon name="arrowRight" size={16} />
                        </button>
                    </div>
                }
            />
            <ul
                ref={trackRef}
                aria-label={title}
                className="flex snap-x snap-mandatory [scrollbar-width:none] gap-4 overflow-x-auto pb-3 [&::-webkit-scrollbar]:hidden"
            >
                {products.map((product) => (
                    <li
                        key={product.id}
                        className="w-44 shrink-0 snap-start sm:w-52 lg:w-[calc((100%-4rem)/5)]"
                    >
                        <ProductCard
                            product={product}
                            action={renderAction?.(product)}
                        />
                    </li>
                ))}
            </ul>
        </section>
    );
}

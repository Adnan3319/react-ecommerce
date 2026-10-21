import { useCallback, useEffect, useRef, useState } from 'react';
import type { ComponentProps } from 'react';
import ProductCard from '@/storefront/components/ProductCard';
import { Icon, SectionHeader } from '@/storefront/components/ui';
import type { ProductCardData } from '@/storefront/features/catalog/types';

type ProductRailProps = Pick<
    ComponentProps<typeof SectionHeader>,
    'title' | 'viewAll'
> & {
    products: ProductCardData[];
    /** How many leading cards load their image right away (the first visible row). */
    eagerCount?: number;
};

type ScrollEdges = { atStart: boolean; atEnd: boolean };

const EDGE_TOLERANCE_PX = 2;

const ARROW_CLASS =
    'flex size-9 items-center justify-center rounded-full border border-neutral-300 bg-white text-neutral-800 transition-colors hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-white';

/**
 * Horizontal product carousel: native scroll with snap points (swipe on touch, arrows on desktop),
 * 2.2 cards in view on mobile (the next one peeks), 3 / 4 / 5 from `md` / `lg` / `xl`.
 */
export default function ProductRail({
    title,
    viewAll,
    products,
    eagerCount = 0,
}: ProductRailProps) {
    const listRef = useRef<HTMLUListElement>(null);
    const [edges, setEdges] = useState<ScrollEdges>({
        atStart: true,
        atEnd: false,
    });

    const syncEdges = useCallback(() => {
        const list = listRef.current;

        if (list === null) {
            return;
        }

        const atStart = list.scrollLeft <= EDGE_TOLERANCE_PX;
        const atEnd =
            list.scrollLeft + list.clientWidth >=
            list.scrollWidth - EDGE_TOLERANCE_PX;

        setEdges((current) =>
            current.atStart === atStart && current.atEnd === atEnd
                ? current
                : { atStart, atEnd },
        );
    }, []);

    useEffect(() => {
        const list = listRef.current;

        if (list === null) {
            return;
        }

        syncEdges();

        const observer = new ResizeObserver(syncEdges);
        observer.observe(list);

        return () => observer.disconnect();
    }, [syncEdges, products]);

    function scrollPage(direction: 1 | -1): void {
        const list = listRef.current;

        list?.scrollBy({
            left: direction * list.clientWidth,
            behavior: 'smooth',
        });
    }

    if (products.length === 0) {
        return null;
    }

    const arrows = (
        <div className="hidden items-center gap-2 md:flex pointer-coarse:hidden">
            <button
                type="button"
                aria-label={`Previous ${title}`}
                disabled={edges.atStart}
                onClick={() => scrollPage(-1)}
                className={ARROW_CLASS}
            >
                <Icon name="arrowRight" size={16} className="rotate-180" />
            </button>
            <button
                type="button"
                aria-label={`Next ${title}`}
                disabled={edges.atEnd}
                onClick={() => scrollPage(1)}
                className={ARROW_CLASS}
            >
                <Icon name="arrowRight" size={16} />
            </button>
        </div>
    );

    return (
        <section className="mx-auto max-w-7xl px-4 pt-6 sm:px-6">
            <SectionHeader
                title={title}
                viewAll={viewAll}
                action={arrows}
                accent
            />
            <ul
                ref={listRef}
                onScroll={syncEdges}
                className="-mx-4 flex snap-x snap-mandatory scroll-px-4 [scrollbar-width:none] gap-4 overflow-x-auto scroll-smooth px-4 pb-2 sm:mx-0 sm:scroll-px-0 sm:px-0 [&::-webkit-scrollbar]:hidden"
            >
                {products.map((product, index) => (
                    <li
                        key={product.id}
                        className="w-[calc((100%-1.2rem)/2.2)] shrink-0 snap-start md:w-[calc((100%-2rem)/3)] lg:w-[calc((100%-3rem)/4)] xl:w-[calc((100%-4rem)/5)]"
                    >
                        <ProductCard
                            product={product}
                            eager={index < eagerCount}
                        />
                    </li>
                ))}
            </ul>
        </section>
    );
}

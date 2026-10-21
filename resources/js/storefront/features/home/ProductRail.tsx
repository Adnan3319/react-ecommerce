import type { ComponentProps } from 'react';
import { SectionHeader } from '@/storefront/components/ui';
import ProductCard from '@/storefront/features/catalog/ProductCard';
import type { ProductCardData } from '@/storefront/features/catalog/types';

type ProductRailProps = Pick<
    ComponentProps<typeof SectionHeader>,
    'title' | 'viewAll'
> & {
    products: ProductCardData[];
    /** How many leading cards load their image right away (the first visible row). */
    eagerCount?: number;
};

export default function ProductRail({
    title,
    viewAll,
    products,
    eagerCount = 0,
}: ProductRailProps) {
    if (products.length === 0) {
        return null;
    }

    return (
        <section className="mx-auto max-w-7xl px-4 pt-12 sm:px-6">
            <SectionHeader title={title} viewAll={viewAll} accent />
            <ul className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                {products.map((product, index) => (
                    <li key={product.id}>
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

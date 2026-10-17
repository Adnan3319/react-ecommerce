import type { ReactNode } from 'react';
import type { ProductSummary } from '../../types';
import { ProductCard } from '../ProductCard';

type ProductGridProps = {
    products: ProductSummary[];
    renderAction?: (product: ProductSummary) => ReactNode;
};

export default function ProductGrid({
    products,
    renderAction,
}: ProductGridProps) {
    return (
        <ul className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5">
            {products.map((product) => (
                <li key={product.id}>
                    <ProductCard
                        product={product}
                        action={renderAction?.(product)}
                    />
                </li>
            ))}
        </ul>
    );
}

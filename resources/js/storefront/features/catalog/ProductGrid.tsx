import ProductCard from '@/storefront/components/ProductCard';
import type { ProductCardData } from './types';

type ProductGridProps = {
    products: ProductCardData[];
    /** How many leading cards load their image right away (the first visible row). */
    eagerCount?: number;
};

const FIRST_ROW_CARDS = 3;

export default function ProductGrid({
    products,
    eagerCount = FIRST_ROW_CARDS,
}: ProductGridProps) {
    return (
        <ul className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4">
            {products.map((product, index) => (
                <li key={product.id}>
                    <ProductCard product={product} eager={index < eagerCount} />
                </li>
            ))}
        </ul>
    );
}

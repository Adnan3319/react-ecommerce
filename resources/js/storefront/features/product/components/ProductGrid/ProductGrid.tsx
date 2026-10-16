import type { ProductSummary } from '../../types';
import { ProductCard } from '../ProductCard';

type ProductGridProps = {
    products: ProductSummary[];
};

export default function ProductGrid({ products }: ProductGridProps) {
    return (
        <ul className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5">
            {products.map((product) => (
                <li key={product.id}>
                    <ProductCard product={product} />
                </li>
            ))}
        </ul>
    );
}

import { search } from '@/routes';
import { SectionHeader } from '@/shared/ui';
import { AddToCartButton } from '@/storefront/features/cart';
import { ProductGrid, SAMPLE_PRODUCTS } from '@/storefront/features/product';

export default function FeaturedProducts() {
    return (
        <section className="mx-auto max-w-7xl px-4 pt-10 sm:px-6">
            <SectionHeader
                title="Our Products"
                viewAll={{ label: 'View All Products', href: search() }}
            />
            <ProductGrid
                products={SAMPLE_PRODUCTS}
                renderAction={(product) => (
                    <AddToCartButton
                        product={product}
                        size="sm"
                        fullWidth
                        className="mt-auto"
                    />
                )}
            />
        </section>
    );
}

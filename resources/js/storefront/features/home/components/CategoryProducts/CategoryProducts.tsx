import { CATEGORIES } from '@/storefront/config/site';
import { ProductCardAddToCart } from '@/storefront/features/cart';
import { ProductRail, SAMPLE_PRODUCTS } from '@/storefront/features/product';
import { categoryHref } from '@/storefront/lib/category-href';

/** One product slider per category, in category order. */
export default function CategoryProducts() {
    return (
        <>
            {CATEGORIES.map((category) => {
                const products = SAMPLE_PRODUCTS.filter(
                    (product) => product.categorySlug === category.slug,
                );

                if (products.length === 0) {
                    return null;
                }

                return (
                    <ProductRail
                        key={category.slug}
                        title={category.label}
                        subtitle={category.description ?? undefined}
                        viewAll={{
                            label: 'View All',
                            href: categoryHref(category.slug),
                        }}
                        products={products}
                        renderAction={(product) => (
                            <ProductCardAddToCart product={product} />
                        )}
                    />
                );
            })}
        </>
    );
}

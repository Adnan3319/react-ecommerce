import { ProductRail, SAMPLE_PRODUCTS } from '@/storefront/features/product';
import { categoryHref } from '@/storefront/lib/category-href';
import { useCategories } from '@/storefront/lib/useCategories';

/** One product slider per category, in category order. */
export default function CategoryProducts() {
    const categories = useCategories();

    return (
        <>
            {categories.map((category) => {
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
                    />
                );
            })}
        </>
    );
}

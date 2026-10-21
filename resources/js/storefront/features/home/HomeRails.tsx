import { search } from '@/routes';
import { show } from '@/routes/categories';
import type { ProductCardData } from '@/storefront/features/catalog/types';
import type { ApiSlider } from './hero-slides';
import ProductRail from './ProductRail';
import PromoBanner from './PromoBanner';
import type { CategoryRail } from './types';

type HomeRailsProps = {
    featured: ProductCardData[];
    categoryRails: CategoryRail[];
    promo: ApiSlider | null;
};

/** Leading cards of the first rail that load right away (the first visible row at desktop width). */
const FIRST_ROW_CARDS = 5;

/**
 * Featured products, then one rail per root category. The promo banner sits after the first category
 * rail (between Kitchenware and Fashion when there are two), or after Featured when there is none.
 */
export default function HomeRails({
    featured,
    categoryRails,
    promo,
}: HomeRailsProps) {
    const viewAll = { label: 'View all', href: search() };
    const featuredEager = featured.length > 0 ? FIRST_ROW_CARDS : 0;

    return (
        <>
            <ProductRail
                title="Featured Products"
                viewAll={viewAll}
                products={featured}
                eagerCount={featuredEager}
            />
            {categoryRails.length === 0 && <PromoBanner promo={promo} />}
            {categoryRails.map((rail, index) => (
                <div key={rail.category.slug}>
                    <ProductRail
                        title={rail.category.name}
                        viewAll={{
                            label: 'View all',
                            href: show(rail.category.slug),
                        }}
                        products={rail.products}
                        eagerCount={
                            index === 0 && featuredEager === 0
                                ? FIRST_ROW_CARDS
                                : 0
                        }
                    />
                    {index === 0 && <PromoBanner promo={promo} />}
                </div>
            ))}
        </>
    );
}

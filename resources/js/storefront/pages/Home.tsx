import { search } from '@/routes';
import { Seo } from '@/shared/components';
import type { Seo as SeoData } from '@/shared/types';
import type { ProductCardData } from '@/storefront/features/catalog/types';
import CategoryShowcase from '@/storefront/features/home/CategoryShowcase';
import HeroSlider from '@/storefront/features/home/HeroSlider';
import type { ApiSlider } from '@/storefront/features/home/hero-slides';
import ProductRail from '@/storefront/features/home/ProductRail';
import PromoBanner from '@/storefront/features/home/PromoBanner';
import Testimonials from '@/storefront/features/home/Testimonials';
import TrustStrip from '@/storefront/features/home/TrustStrip';
import type {
    ApiTestimonial,
    DeliveryZone,
    HomeCategory,
} from '@/storefront/features/home/types';

type HomeProps = {
    seo: SeoData;
    heroSlides: ApiSlider[];
    promo: ApiSlider | null;
    categories: HomeCategory[];
    featured: ProductCardData[];
    newArrivals: ProductCardData[];
    testimonials: ApiTestimonial[];
    deliveryZones: DeliveryZone[];
};

const FIRST_ROW_CARDS = 4;

export default function Home({
    seo,
    heroSlides,
    promo,
    categories,
    featured,
    newArrivals,
    testimonials,
    deliveryZones,
}: HomeProps) {
    const viewAll = { label: 'View all', href: search() };

    return (
        <div className="bg-neutral-50">
            <Seo seo={seo} />
            <HeroSlider sliders={heroSlides} />
            <TrustStrip deliveryZones={deliveryZones} />
            <CategoryShowcase categories={categories} />
            <ProductRail
                title="Featured Products"
                viewAll={viewAll}
                products={featured}
                eagerCount={FIRST_ROW_CARDS}
            />
            <PromoBanner promo={promo} />
            <ProductRail
                title="New Arrivals"
                viewAll={viewAll}
                products={newArrivals}
            />
            <Testimonials testimonials={testimonials} />
        </div>
    );
}

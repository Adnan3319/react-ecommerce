import { search } from '@/routes';
import { Seo } from '@/shared/components';
import type { Seo as SeoData } from '@/shared/types';
import type { ProductCardData } from '@/storefront/features/catalog/types';
import CategoryShowcase from '@/storefront/features/home/CategoryShowcase';
import HeroSlider from '@/storefront/features/home/HeroSlider';
import type { ApiSlider } from '@/storefront/features/home/hero-slides';
import HomeRails from '@/storefront/features/home/HomeRails';
import ProductRail from '@/storefront/features/home/ProductRail';
import Testimonials from '@/storefront/features/home/Testimonials';
import TrustStrip from '@/storefront/features/home/TrustStrip';
import type {
    ApiTestimonial,
    CategoryRail,
    DeliveryZone,
    HomeCategory,
} from '@/storefront/features/home/types';

type HomeProps = {
    seo: SeoData;
    heroSlides: ApiSlider[];
    promo: ApiSlider | null;
    categories: HomeCategory[];
    featured: ProductCardData[];
    categoryRails: CategoryRail[];
    newArrivals: ProductCardData[];
    testimonials: ApiTestimonial[];
    deliveryZones: DeliveryZone[];
};

export default function Home({
    seo,
    heroSlides,
    promo,
    categories,
    featured,
    categoryRails,
    newArrivals,
    testimonials,
    deliveryZones,
}: HomeProps) {
    return (
        <div className="bg-neutral-50">
            <Seo seo={seo} />
            <HeroSlider sliders={heroSlides}>
                <TrustStrip deliveryZones={deliveryZones} />
            </HeroSlider>
            <CategoryShowcase categories={categories} />
            <HomeRails
                featured={featured}
                categoryRails={categoryRails}
                promo={promo}
            />
            <ProductRail
                title="New Arrivals"
                viewAll={{ label: 'View all', href: search() }}
                products={newArrivals}
            />
            <Testimonials testimonials={testimonials} />
        </div>
    );
}

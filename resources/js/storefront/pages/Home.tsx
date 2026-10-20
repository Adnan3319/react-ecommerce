import { Seo } from '@/shared/components';
import type { Seo as SeoData } from '@/shared/types';
import {
    CategoryProducts,
    CategoryShowcase,
    HeroSlider,
    PromoBanner,
    Testimonials,
} from '@/storefront/features/home';
import type { ApiSlider, ApiTestimonial } from '@/storefront/features/home';

type HomeProps = {
    seo: SeoData;
    sliders: ApiSlider[];
    /** First active promo slider; wired into the page by the landing step. */
    promo: ApiSlider | null;
    /** Real testimonials; the landing step renders them. */
    testimonials: ApiTestimonial[];
};

export default function Home({ seo, sliders }: HomeProps) {
    return (
        <div className="bg-neutral-50">
            <Seo seo={seo} />
            <HeroSlider sliders={sliders} />
            <CategoryShowcase />
            <CategoryProducts />
            <PromoBanner />
            <Testimonials />
        </div>
    );
}

import { Seo } from '@/shared/components';
import type { Seo as SeoData } from '@/shared/types';
import {
    CategoryProducts,
    CategoryShowcase,
    HeroSlider,
    PromoBanner,
    Testimonials,
} from '@/storefront/features/home';
import type { ApiSlider } from '@/storefront/features/home';

type HomeProps = {
    seo: SeoData;
    sliders: ApiSlider[];
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

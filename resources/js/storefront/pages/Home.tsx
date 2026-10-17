import { Seo } from '@/shared/components';
import type { Seo as SeoData } from '@/shared/types';
import {
    CategoryProducts,
    CategoryShowcase,
    HeroSlider,
    PromoBanner,
    Testimonials,
} from '@/storefront/features/home';

type HomeProps = {
    seo: SeoData;
};

export default function Home({ seo }: HomeProps) {
    return (
        <div className="bg-neutral-50">
            <Seo seo={seo} />
            <HeroSlider />
            <CategoryShowcase />
            <CategoryProducts />
            <PromoBanner />
            <Testimonials />
        </div>
    );
}

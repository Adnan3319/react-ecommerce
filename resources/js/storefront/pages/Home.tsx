import { Seo } from '@/shared/components';
import type { Seo as SeoData } from '@/shared/types';
import {
    CategoryShowcase,
    FeaturedProducts,
    HeroSlider,
    PromoBanner,
    Testimonials,
    ValueProps,
} from '@/storefront/features/home';

type HomeProps = {
    seo: SeoData;
};

export default function Home({ seo }: HomeProps) {
    return (
        <>
            <Seo seo={seo} />
            <HeroSlider />
            <ValueProps />
            <CategoryShowcase />
            <FeaturedProducts />
            <PromoBanner />
            <Testimonials />
        </>
    );
}

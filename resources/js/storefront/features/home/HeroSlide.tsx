import { Link } from '@inertiajs/react';
import { Icon } from '@/storefront/components/ui';
import type { HeroSlide as HeroSlideData } from './hero-slides';

type HeroSlideProps = {
    slide: HeroSlideData;
};

/** Text and call-to-action of one hero slide (the photo lives in HeroSlider). */
export default function HeroSlide({ slide }: HeroSlideProps) {
    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col justify-center px-4 py-14 sm:px-6 md:min-h-[27rem]">
            <h2 className="max-w-lg text-4xl leading-[1.05] font-extrabold tracking-tight sm:text-6xl">
                {slide.title}
                {slide.highlight && (
                    <span className="block bg-linear-to-r from-accent-500 to-orange-400 bg-clip-text text-transparent">
                        {slide.highlight}
                    </span>
                )}
            </h2>
            {slide.subtitle && (
                <p className="mt-4 max-w-sm text-base text-white/85">
                    {slide.subtitle}
                </p>
            )}
            {slide.cta && (
                <Link
                    href={slide.cta.href}
                    className="mt-6 inline-flex h-12 w-fit items-center gap-2 rounded-full btn-accent px-7 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                >
                    {slide.cta.label}
                    <Icon name="arrowRight" size={16} />
                </Link>
            )}
        </div>
    );
}

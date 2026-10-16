import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { cn } from '@/shared/lib';
import { Badge, Icon, Image } from '@/shared/ui';
import { CATEGORIES } from '@/storefront/config/site';
import { categoryHref } from '@/storefront/lib/category-href';
import { HERO_SLIDES } from '../../hero-slides';

const AUTOPLAY_MS = 6000;
const SLIDE_COUNT = HERO_SLIDES.length;
const [KITCHENWARE] = CATEGORIES;

const ARROW_CLASS =
    'absolute top-1/2 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-neutral-800 shadow-card transition-colors hover:bg-white md:flex';

export default function HeroSlider() {
    const [index, setIndex] = useState(0);
    const [isPaused, setIsPaused] = useState(false);

    useEffect(() => {
        const prefersReducedMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        if (isPaused || prefersReducedMotion) {
            return;
        }

        const timer = window.setInterval(
            () => setIndex((current) => (current + 1) % SLIDE_COUNT),
            AUTOPLAY_MS,
        );

        return () => window.clearInterval(timer);
    }, [isPaused]);

    const goTo = (target: number) =>
        setIndex((target + SLIDE_COUNT) % SLIDE_COUNT);

    return (
        <section
            aria-roledescription="carousel"
            aria-label="Featured collections"
            className="mx-auto max-w-7xl px-4 pt-6 sm:px-6"
            onMouseEnter={() => setIsPaused(true)}
            onMouseLeave={() => setIsPaused(false)}
            onFocus={() => setIsPaused(true)}
            onBlur={() => setIsPaused(false)}
        >
            <h1 className="sr-only">Kitchenware and fashion store</h1>

            <div className="relative overflow-hidden rounded-card bg-cream-100">
                <div className="grid">
                    {HERO_SLIDES.map((slide, slideIndex) => {
                        const isActive = slideIndex === index;

                        return (
                            <div
                                key={slide.id}
                                role="group"
                                aria-roledescription="slide"
                                aria-label={`${slideIndex + 1} of ${SLIDE_COUNT}`}
                                inert={!isActive}
                                className={cn(
                                    'col-start-1 row-start-1 grid items-center gap-6 p-6 pb-12 transition-opacity duration-700 sm:p-10 sm:pb-14 md:grid-cols-2 md:px-16',
                                    isActive
                                        ? 'opacity-100'
                                        : 'pointer-events-none opacity-0',
                                )}
                            >
                                <div className="order-last md:order-first">
                                    <Badge tone="accent">{slide.eyebrow}</Badge>
                                    <h2 className="mt-4 text-3xl font-bold tracking-tight text-neutral-900 sm:text-5xl">
                                        {slide.title}
                                    </h2>
                                    <p className="mt-4 max-w-md text-base text-neutral-600">
                                        {slide.description}
                                    </p>
                                    <Link
                                        href={categoryHref(KITCHENWARE.slug)}
                                        className="mt-6 inline-flex h-11 items-center gap-2 rounded-control bg-accent-600 px-5 text-sm font-semibold text-white transition-colors hover:bg-accent-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600"
                                    >
                                        Shop Kitchenware
                                        <Icon name="arrowRight" size={16} />
                                    </Link>
                                </div>

                                <div className="flex justify-center">
                                    <Image
                                        src={slide.image}
                                        alt={slide.imageAlt}
                                        width={slide.imageWidth}
                                        height={slide.imageHeight}
                                        priority={slideIndex === 0}
                                        className="max-h-72 w-auto rounded-card object-contain shadow-card md:max-h-96"
                                    />
                                </div>
                            </div>
                        );
                    })}
                </div>

                <button
                    type="button"
                    aria-label="Previous slide"
                    onClick={() => goTo(index - 1)}
                    className={cn(ARROW_CLASS, 'left-4')}
                >
                    <Icon name="arrowRight" size={18} className="rotate-180" />
                </button>
                <button
                    type="button"
                    aria-label="Next slide"
                    onClick={() => goTo(index + 1)}
                    className={cn(ARROW_CLASS, 'right-4')}
                >
                    <Icon name="arrowRight" size={18} />
                </button>

                <div className="absolute inset-x-0 bottom-3 flex justify-center gap-1">
                    {HERO_SLIDES.map((slide, slideIndex) => (
                        <button
                            key={slide.id}
                            type="button"
                            aria-label={`Go to slide ${slideIndex + 1}`}
                            aria-current={slideIndex === index}
                            onClick={() => goTo(slideIndex)}
                            className="flex h-6 w-6 items-center justify-center"
                        >
                            <span
                                className={cn(
                                    'h-2.5 rounded-full transition-all',
                                    slideIndex === index
                                        ? 'w-6 bg-accent-600'
                                        : 'w-2.5 bg-neutral-400/60',
                                )}
                            />
                        </button>
                    ))}
                </div>
            </div>
        </section>
    );
}

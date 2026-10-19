import { Link } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { cn } from '@/shared/lib';
import { Icon, Image } from '@/shared/ui';
import { HERO_SLIDES, toHeroSlide } from '../../hero-slides';
import type { ApiSlider } from '../../hero-slides';
import { ValueProps } from '../ValueProps';

const AUTOPLAY_MS = 6000;

const PAGER_BUTTON_CLASS =
    'flex size-8 items-center justify-center rounded-full transition-colors hover:bg-white/15';

type HeroSliderProps = {
    /** Active sliders from the backend. Sample slides show when this is empty. */
    sliders?: ApiSlider[];
};

export default function HeroSlider({ sliders = [] }: HeroSliderProps) {
    const slides = useMemo(
        () => (sliders.length > 0 ? sliders.map(toHeroSlide) : HERO_SLIDES),
        [sliders],
    );
    const slideCount = slides.length;

    const [index, setIndex] = useState(0);
    const [isPaused, setIsPaused] = useState(false);

    // Keep the index valid if the slide list gets shorter.
    const currentIndex = index < slideCount ? index : 0;

    useEffect(() => {
        const prefersReducedMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        if (isPaused || prefersReducedMotion || slideCount < 2) {
            return;
        }

        const timer = window.setInterval(
            () => setIndex((current) => (current + 1) % slideCount),
            AUTOPLAY_MS,
        );

        return () => window.clearInterval(timer);
    }, [isPaused, slideCount]);

    const goTo = (target: number) =>
        setIndex((target + slideCount) % slideCount);

    return (
        <section
            aria-roledescription="carousel"
            aria-label="Featured collections"
            className="relative overflow-hidden bg-shaded text-white"
            onMouseEnter={() => setIsPaused(true)}
            onMouseLeave={() => setIsPaused(false)}
            onFocus={() => setIsPaused(true)}
            onBlur={() => setIsPaused(false)}
        >
            <h1 className="sr-only">Kitchenware and fashion store</h1>

            {/* Photo fills the right side edge to edge and fades into the dark left. */}
            {slides.map((slide, slideIndex) => (
                <div
                    key={slide.id}
                    aria-hidden="true"
                    className={cn(
                        'absolute inset-y-0 right-0 w-full transition-opacity duration-700 md:w-[58%] md:mask-[linear-gradient(to_right,transparent,black_30%)]',
                        slideIndex === currentIndex
                            ? 'opacity-100'
                            : 'opacity-0',
                    )}
                >
                    <Image
                        src={slide.image}
                        alt=""
                        width={slide.imageWidth}
                        height={slide.imageHeight}
                        priority={slideIndex === 0}
                        className="h-full w-full object-cover"
                    />
                </div>
            ))}
            <div
                aria-hidden="true"
                className="absolute inset-0 bg-linear-to-t from-ink/70 via-transparent to-ink/50 md:bg-linear-to-t md:from-ink/60 md:via-transparent md:to-transparent"
            />
            <div
                aria-hidden="true"
                className="absolute inset-0 bg-ink/60 md:hidden"
            />

            <div className="relative md:pt-[4.5rem]">
                <div className="grid">
                    {slides.map((slide, slideIndex) => {
                        const isActive = slideIndex === currentIndex;

                        return (
                            <div
                                key={slide.id}
                                role="group"
                                aria-roledescription="slide"
                                aria-label={`${slideIndex + 1} of ${slideCount}. ${slide.imageAlt}`}
                                inert={!isActive}
                                className={cn(
                                    'col-start-1 row-start-1 mx-auto flex w-full max-w-7xl flex-col justify-center px-4 py-14 transition-opacity duration-700 sm:px-6 md:min-h-[27rem]',
                                    isActive
                                        ? 'opacity-100'
                                        : 'pointer-events-none opacity-0',
                                )}
                            >
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
                    })}
                </div>

                {slideCount > 1 && (
                    <div className="absolute top-1/3 right-4 hidden flex-col items-center gap-1 rounded-full bg-ink/80 px-1 py-2 text-xs text-white shadow-lg ring-1 ring-white/30 backdrop-blur-md lg:flex">
                        <button
                            type="button"
                            aria-label="Previous slide"
                            onClick={() => goTo(currentIndex - 1)}
                            className={PAGER_BUTTON_CLASS}
                        >
                            <Icon
                                name="arrowRight"
                                size={14}
                                className="-rotate-90"
                            />
                        </button>
                        <span aria-hidden="true">
                            {currentIndex + 1} / {slideCount}
                        </span>
                        <button
                            type="button"
                            aria-label="Next slide"
                            onClick={() => goTo(currentIndex + 1)}
                            className={PAGER_BUTTON_CLASS}
                        >
                            <Icon
                                name="arrowRight"
                                size={14}
                                className="rotate-90"
                            />
                        </button>
                    </div>
                )}

                {slideCount > 1 && (
                    <div className="flex justify-center gap-1 lg:hidden">
                        {slides.map((slide, slideIndex) => (
                            <button
                                key={slide.id}
                                type="button"
                                aria-label={`Go to slide ${slideIndex + 1}`}
                                aria-current={slideIndex === currentIndex}
                                onClick={() => goTo(slideIndex)}
                                className="flex size-6 items-center justify-center"
                            >
                                <span
                                    className={cn(
                                        'h-2 rounded-full transition-all',
                                        slideIndex === currentIndex
                                            ? 'w-6 bg-accent-500'
                                            : 'w-2 bg-white/40',
                                    )}
                                />
                            </button>
                        ))}
                    </div>
                )}

                <ValueProps />
            </div>

            <svg
                aria-hidden="true"
                viewBox="0 0 1440 48"
                preserveAspectRatio="none"
                className="absolute inset-x-0 bottom-0 h-10 w-full translate-y-px text-neutral-50 md:h-12"
            >
                <path
                    d="M0 48V26C240 6 520 -6 820 12c280 17 480 24 620 8v28z"
                    fill="currentColor"
                />
            </svg>
        </section>
    );
}

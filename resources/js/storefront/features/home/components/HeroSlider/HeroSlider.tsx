import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { cn } from '@/shared/lib';
import { Icon, Image } from '@/shared/ui';
import { HERO_SLIDES } from '../../hero-slides';
import { ValueProps } from '../ValueProps';

const AUTOPLAY_MS = 6000;
const SLIDE_COUNT = HERO_SLIDES.length;

const PAGER_BUTTON_CLASS =
    'flex size-8 items-center justify-center rounded-full transition-colors hover:bg-white/15';

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
            className="relative overflow-hidden bg-shaded text-white"
            onMouseEnter={() => setIsPaused(true)}
            onMouseLeave={() => setIsPaused(false)}
            onFocus={() => setIsPaused(true)}
            onBlur={() => setIsPaused(false)}
        >
            <h1 className="sr-only">Kitchenware and fashion store</h1>

            {/* Photo fills the right side edge to edge and fades into the dark left. */}
            {HERO_SLIDES.map((slide, slideIndex) => (
                <div
                    key={slide.id}
                    aria-hidden="true"
                    className={cn(
                        'absolute inset-y-0 right-0 w-full transition-opacity duration-700 md:w-[58%] md:mask-[linear-gradient(to_right,transparent,black_30%)]',
                        slideIndex === index ? 'opacity-100' : 'opacity-0',
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
                    {HERO_SLIDES.map((slide, slideIndex) => {
                        const isActive = slideIndex === index;

                        return (
                            <div
                                key={slide.id}
                                role="group"
                                aria-roledescription="slide"
                                aria-label={`${slideIndex + 1} of ${SLIDE_COUNT}. ${slide.imageAlt}`}
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
                                <p className="mt-4 max-w-sm text-base text-white/85">
                                    {slide.subtitle}
                                </p>
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

                <div className="absolute top-1/3 right-4 hidden flex-col items-center gap-1 rounded-full bg-ink/80 px-1 py-2 text-xs text-white shadow-lg ring-1 ring-white/30 backdrop-blur-md lg:flex">
                    <button
                        type="button"
                        aria-label="Previous slide"
                        onClick={() => goTo(index - 1)}
                        className={PAGER_BUTTON_CLASS}
                    >
                        <Icon
                            name="arrowRight"
                            size={14}
                            className="-rotate-90"
                        />
                    </button>
                    <span aria-hidden="true">
                        {index + 1} / {SLIDE_COUNT}
                    </span>
                    <button
                        type="button"
                        aria-label="Next slide"
                        onClick={() => goTo(index + 1)}
                        className={PAGER_BUTTON_CLASS}
                    >
                        <Icon
                            name="arrowRight"
                            size={14}
                            className="rotate-90"
                        />
                    </button>
                </div>

                <div className="flex justify-center gap-1 lg:hidden">
                    {HERO_SLIDES.map((slide, slideIndex) => (
                        <button
                            key={slide.id}
                            type="button"
                            aria-label={`Go to slide ${slideIndex + 1}`}
                            aria-current={slideIndex === index}
                            onClick={() => goTo(slideIndex)}
                            className="flex size-6 items-center justify-center"
                        >
                            <span
                                className={cn(
                                    'h-2 rounded-full transition-all',
                                    slideIndex === index
                                        ? 'w-6 bg-accent-500'
                                        : 'w-2 bg-white/40',
                                )}
                            />
                        </button>
                    ))}
                </div>

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

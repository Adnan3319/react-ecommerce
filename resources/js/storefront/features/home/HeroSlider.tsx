import {
    Suspense,
    lazy,
    useCallback,
    useEffect,
    useMemo,
    useState,
    type ReactNode,
} from 'react';
import type { Swiper as SwiperInstance } from 'swiper/types';
import { cn } from '@/shared/lib';
import { Icon, Image } from '@/storefront/components/ui';
import HeroSlide from './HeroSlide';
import { HERO_SLIDES, toHeroSlide } from './hero-slides';
import type { ApiSlider } from './hero-slides';

// Swiper (JS + CSS) is a separate chunk, requested only after the first paint.
const HeroSwiper = lazy(() => import('./HeroSwiper'));

const IDLE_TIMEOUT_MS = 2000;

const PAGER_BUTTON_CLASS =
    'flex size-8 items-center justify-center rounded-full transition-colors hover:bg-white/15';

type HeroSliderProps = {
    /** Active sliders from the backend. Sample slides show when this is empty. */
    sliders?: ApiSlider[];
    /** Rendered at the bottom of the hero (the trust strip). */
    children?: ReactNode;
};

function addIndex(
    current: ReadonlySet<number>,
    index: number,
): ReadonlySet<number> {
    return current.has(index) ? current : new Set(current).add(index);
}

export default function HeroSlider({
    sliders = [],
    children,
}: HeroSliderProps) {
    const slides = useMemo(
        () => (sliders.length > 0 ? sliders.map(toHeroSlide) : HERO_SLIDES),
        [sliders],
    );
    const slideCount = slides.length;
    const hasMany = slideCount > 1;

    const [swiperRequested, setSwiperRequested] = useState(false);
    const [swiper, setSwiper] = useState<SwiperInstance | null>(null);
    const [activeIndex, setActiveIndex] = useState(0);
    const [isPaused, setIsPaused] = useState(false);
    // Photos that may be rendered: the first slide, then only the next one at a time.
    const [revealed, setRevealed] = useState<ReadonlySet<number>>(new Set([0]));
    const [loaded, setLoaded] = useState<ReadonlySet<number>>(new Set());

    const currentIndex = activeIndex < slideCount ? activeIndex : 0;
    const nextIndex = (currentIndex + 1) % slideCount;

    // Ask for Swiper only once the page has painted and there is something to slide.
    useEffect(() => {
        if (!hasMany) {
            return;
        }

        if (typeof window.requestIdleCallback === 'function') {
            const handle = window.requestIdleCallback(
                () => setSwiperRequested(true),
                { timeout: IDLE_TIMEOUT_MS },
            );

            return () => window.cancelIdleCallback(handle);
        }

        const timer = window.setTimeout(() => setSwiperRequested(true), 200);

        return () => window.clearTimeout(timer);
    }, [hasMany]);

    // Load only the next photo, never the rest.
    useEffect(() => {
        if (swiper) {
            setRevealed((current) => addIndex(current, nextIndex));
        }
    }, [swiper, nextIndex]);

    // Autoplay runs only when the next photo is ready, the user is not hovering and motion is allowed.
    useEffect(() => {
        if (!swiper) {
            return;
        }

        const reducedMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        if (isPaused || reducedMotion || !loaded.has(nextIndex)) {
            swiper.autoplay.stop();

            return;
        }

        swiper.autoplay.start();
    }, [swiper, isPaused, loaded, nextIndex]);

    const markLoaded = useCallback(
        (index: number) => setLoaded((current) => addIndex(current, index)),
        [],
    );

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
            {slides.map((slide, slideIndex) =>
                revealed.has(slideIndex) ? (
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
                            srcSet={slide.imageSrcSet ?? undefined}
                            sizes="100vw"
                            alt=""
                            width={slide.imageWidth}
                            height={slide.imageHeight}
                            priority={slideIndex === 0}
                            ref={(element) => {
                                if (element?.complete) {
                                    markLoaded(slideIndex);
                                }
                            }}
                            onLoad={() => markLoaded(slideIndex)}
                            className="h-full w-full object-cover"
                        />
                    </div>
                ) : null,
            )}
            <div
                aria-hidden="true"
                className="absolute inset-0 bg-linear-to-t from-ink/70 via-transparent to-ink/50 md:bg-linear-to-t md:from-ink/60 md:via-transparent md:to-transparent"
            />
            <div
                aria-hidden="true"
                className="absolute inset-0 bg-ink/60 md:hidden"
            />

            <div className="relative md:pt-[4.5rem]">
                {swiperRequested ? (
                    <Suspense
                        fallback={
                            <StaticSlide slide={slides[0]} count={slideCount} />
                        }
                    >
                        <HeroSwiper
                            slides={slides}
                            onSwiper={setSwiper}
                            onActiveChange={setActiveIndex}
                        />
                    </Suspense>
                ) : (
                    <StaticSlide slide={slides[0]} count={slideCount} />
                )}

                {swiper && (
                    <div className="absolute top-1/3 right-4 hidden flex-col items-center gap-1 rounded-full bg-ink/80 px-1 py-2 text-xs text-white shadow-lg ring-1 ring-white/30 backdrop-blur-md lg:flex">
                        <button
                            type="button"
                            aria-label="Previous slide"
                            onClick={() => swiper.slidePrev()}
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
                            onClick={() => swiper.slideNext()}
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

                {hasMany && (
                    // Height is reserved so the dots appearing later do not shift the layout.
                    <div className="flex min-h-6 justify-center gap-1 lg:hidden">
                        {swiper &&
                            slides.map((slide, slideIndex) => (
                                <button
                                    key={slide.id}
                                    type="button"
                                    aria-label={`Go to slide ${slideIndex + 1}`}
                                    aria-current={slideIndex === currentIndex}
                                    onClick={() => swiper.slideTo(slideIndex)}
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

                {children}
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

type StaticSlideProps = {
    slide: ReturnType<typeof toHeroSlide>;
    count: number;
};

/** First slide as plain markup, shown until the Swiper chunk is ready. */
function StaticSlide({ slide, count }: StaticSlideProps) {
    return (
        <div
            role="group"
            aria-roledescription="slide"
            aria-label={`1 of ${count}. ${slide.imageAlt}`}
        >
            <HeroSlide slide={slide} />
        </div>
    );
}

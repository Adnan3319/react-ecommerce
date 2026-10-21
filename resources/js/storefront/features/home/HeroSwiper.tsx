import { A11y, Autoplay, EffectFade } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';
import type { Swiper as SwiperInstance } from 'swiper/types';
import 'swiper/css';
import 'swiper/css/effect-fade';
import HeroSlide from './HeroSlide';
import type { HeroSlide as HeroSlideData } from './hero-slides';

export const AUTOPLAY_MS = 6000;
const FADE_MS = 700;

type HeroSwiperProps = {
    slides: HeroSlideData[];
    onSwiper: (swiper: SwiperInstance) => void;
    onActiveChange: (index: number) => void;
};

/**
 * Lazy chunk: the only place that imports Swiper. It renders the text slides and handles
 * fading, touch swipe and the autoplay timer. HeroSlider draws the photos and the pager,
 * and starts autoplay only after the next photo has loaded.
 */
export default function HeroSwiper({
    slides,
    onSwiper,
    onActiveChange,
}: HeroSwiperProps) {
    return (
        <Swiper
            className="h-full"
            modules={[A11y, Autoplay, EffectFade]}
            effect="fade"
            fadeEffect={{ crossFade: true }}
            speed={FADE_MS}
            rewind
            autoplay={{ delay: AUTOPLAY_MS, disableOnInteraction: false }}
            a11y={{ slideLabelMessage: 'Slide {{index}} of {{slidesLength}}' }}
            onSwiper={(swiper) => {
                swiper.autoplay.stop();
                onSwiper(swiper);
            }}
            onSlideChange={(swiper) => onActiveChange(swiper.activeIndex)}
        >
            {slides.map((slide) => (
                <SwiperSlide key={slide.id}>
                    <HeroSlide slide={slide} />
                </SwiperSlide>
            ))}
        </Swiper>
    );
}

import { Link } from '@inertiajs/react';
import { Icon, Image } from '@/storefront/components/ui';
import type { ApiSlider } from './hero-slides';

type PromoBannerProps = {
    promo: ApiSlider | null;
};

/** Uploaded promo banners are stored at this size (config/media.php, `promo` preset). */
const PROMO_WIDTH = 1200;
const PROMO_HEIGHT = 800;

export default function PromoBanner({ promo }: PromoBannerProps) {
    if (promo === null) {
        return null;
    }

    return (
        <section
            aria-label={promo.title}
            className="mx-auto max-w-7xl px-4 pt-6 sm:px-6"
        >
            <div className="grid overflow-hidden rounded-card bg-ink text-white md:grid-cols-2">
                <div className="flex flex-col items-start justify-center gap-4 p-6 sm:p-10">
                    <h2 className="text-3xl leading-tight font-extrabold tracking-tight sm:text-4xl">
                        {promo.title}
                    </h2>
                    {promo.subtitle && (
                        <p className="max-w-sm text-sm text-white/70">
                            {promo.subtitle}
                        </p>
                    )}
                    {promo.button_link && (
                        <Link
                            href={promo.button_link}
                            className="inline-flex h-11 items-center gap-2 rounded-full bg-white px-6 text-sm font-semibold text-neutral-900 hover:bg-neutral-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                        >
                            {promo.button_text || 'Shop Now'}
                            <Icon name="arrowRight" size={16} />
                        </Link>
                    )}
                </div>
                <Image
                    src={promo.image}
                    srcSet={promo.image_srcset ?? undefined}
                    sizes="(min-width: 768px) 50vw, 100vw"
                    alt={promo.title}
                    width={PROMO_WIDTH}
                    height={PROMO_HEIGHT}
                    className="aspect-3/2 h-full w-full object-cover"
                />
            </div>
        </section>
    );
}

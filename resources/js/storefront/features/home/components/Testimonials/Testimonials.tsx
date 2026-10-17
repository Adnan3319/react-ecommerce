import { Icon } from '@/shared/ui';
import { TESTIMONIALS } from '../../static-data';

const STAR_COUNT = 5;
const STARS = Array.from({ length: STAR_COUNT }, (_, index) => index);

export default function Testimonials() {
    return (
        <section className="relative mt-12 bg-shaded text-white">
            <svg
                aria-hidden="true"
                viewBox="0 0 1440 48"
                preserveAspectRatio="none"
                className="absolute inset-x-0 top-0 h-8 w-full -translate-y-px rotate-180 text-neutral-50 md:h-12"
            >
                <path
                    d="M0 48V26C240 6 520 -6 820 12c280 17 480 24 620 8v28z"
                    fill="currentColor"
                />
            </svg>
            <div className="mx-auto max-w-7xl px-4 pt-20 pb-12 sm:px-6">
                <h2 className="text-xl font-bold tracking-tight sm:text-2xl">
                    What Our Customers Say
                </h2>
                <p className="mt-1 text-sm text-white/60">
                    Real stories. Real people.
                </p>
                <ul className="mt-6 grid gap-4 md:grid-cols-3">
                    {TESTIMONIALS.map((item) => (
                        <li key={item.id}>
                            <figure className="flex h-full flex-col gap-3 rounded-card bg-white/5 p-5 ring-1 ring-white/10 backdrop-blur-sm">
                                <div className="flex items-center gap-3">
                                    <span
                                        aria-hidden="true"
                                        className="flex size-10 items-center justify-center rounded-full bg-accent-600 text-base font-bold"
                                    >
                                        {item.author.charAt(0)}
                                    </span>
                                    <div>
                                        <figcaption className="text-sm font-semibold">
                                            {item.author}
                                        </figcaption>
                                        <p
                                            role="img"
                                            aria-label={`${STAR_COUNT} out of ${STAR_COUNT} stars`}
                                            className="flex gap-0.5 text-amber-400"
                                        >
                                            {STARS.map((star) => (
                                                <Icon
                                                    key={star}
                                                    name="star"
                                                    size={12}
                                                    fill="currentColor"
                                                />
                                            ))}
                                        </p>
                                    </div>
                                </div>
                                <blockquote className="text-sm text-white/80">
                                    {item.quote}
                                </blockquote>
                            </figure>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}

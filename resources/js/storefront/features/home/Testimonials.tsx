import { cn } from '@/shared/lib';
import { Icon } from '@/storefront/components/ui';
import type { ApiTestimonial } from './types';

type TestimonialsProps = {
    testimonials: ApiTestimonial[];
};

const MAX_RATING = 5;
const STARS = Array.from({ length: MAX_RATING }, (_, index) => index + 1);

export default function Testimonials({ testimonials }: TestimonialsProps) {
    if (testimonials.length === 0) {
        return null;
    }

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
                    {testimonials.map((item) => (
                        <li key={item.id}>
                            <figure className="flex h-full flex-col gap-3 rounded-card bg-white/5 p-5 ring-1 ring-white/10 backdrop-blur-sm">
                                <div className="flex items-center gap-3">
                                    <span
                                        aria-hidden="true"
                                        className="flex size-10 items-center justify-center rounded-full bg-accent-600 text-base font-bold"
                                    >
                                        {item.customer_name.charAt(0)}
                                    </span>
                                    <div>
                                        <figcaption className="text-sm font-semibold">
                                            {item.customer_name}
                                            <span className="ml-2 text-xs font-normal text-white/50">
                                                via {item.source_label}
                                            </span>
                                        </figcaption>
                                        <p
                                            role="img"
                                            aria-label={`${item.rating} out of ${MAX_RATING} stars`}
                                            className="flex gap-0.5"
                                        >
                                            {STARS.map((star) => (
                                                <Icon
                                                    key={star}
                                                    name="star"
                                                    size={12}
                                                    fill="currentColor"
                                                    className={cn(
                                                        star <= item.rating
                                                            ? 'text-amber-400'
                                                            : 'text-white/20',
                                                    )}
                                                />
                                            ))}
                                        </p>
                                    </div>
                                </div>
                                <blockquote className="text-sm text-white/80">
                                    {item.text}
                                </blockquote>
                            </figure>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}

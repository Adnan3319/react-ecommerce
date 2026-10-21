import { cn } from '@/shared/lib';
import { Icon, SectionHeader } from '@/storefront/components/ui';
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
        <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
            <SectionHeader title="What customers say" accent />
            <ul className="grid gap-4 md:grid-cols-3">
                {testimonials.map((item) => (
                    <li key={item.id}>
                        <figure className="flex h-full flex-col gap-4 rounded-card bg-white p-6 shadow-card ring-1 ring-neutral-200">
                            <p
                                role="img"
                                aria-label={`${item.rating} out of ${MAX_RATING} stars`}
                                className="flex gap-0.5"
                            >
                                {STARS.map((star) => (
                                    <Icon
                                        key={star}
                                        name="star"
                                        size={16}
                                        fill="currentColor"
                                        className={cn(
                                            star <= item.rating
                                                ? 'text-amber-400'
                                                : 'text-neutral-200',
                                        )}
                                    />
                                ))}
                            </p>
                            <blockquote className="flex-1 text-sm text-neutral-700">
                                {item.text}
                            </blockquote>
                            <figcaption className="flex items-center justify-between gap-3">
                                <span className="flex items-center gap-3">
                                    <span
                                        aria-hidden="true"
                                        className="flex size-9 items-center justify-center rounded-full bg-accent-100 text-sm font-bold text-accent-700"
                                    >
                                        {item.customer_name.charAt(0)}
                                    </span>
                                    <span className="text-sm font-semibold text-neutral-900">
                                        {item.customer_name}
                                    </span>
                                </span>
                                <span className="text-xs text-neutral-500">
                                    via {item.source_label}
                                </span>
                            </figcaption>
                        </figure>
                    </li>
                ))}
            </ul>
        </section>
    );
}

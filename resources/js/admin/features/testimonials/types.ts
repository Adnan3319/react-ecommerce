import type { ListFilters } from '@admin/hooks/useListFilters';
import type { ActiveStatus, Paginated, StatusOption } from '@/shared/types';

/** Mirrors App\Enums\TestimonialSource. */
export type TestimonialSource = 'facebook' | 'messenger' | 'whatsapp' | 'other';

export type Testimonial = {
    id: number;
    customer_name: string;
    text: string;
    rating: number;
    source: TestimonialSource;
    source_label: string;
    sort_order: number;
    status: ActiveStatus;
    created_at: string | null;
    updated_at: string | null;
};

export type SourceOption = {
    value: TestimonialSource;
    label: string;
};

/** Limits of SaveTestimonialRequest, sent by the controller so they live in one place. */
export type TestimonialLimits = {
    text: number;
    min_rating: number;
    max_rating: number;
};

export type TestimonialIndexProps = {
    testimonials: Paginated<Testimonial>;
    filters: ListFilters;
    statuses: StatusOption[];
    sources: SourceOption[];
    limits: TestimonialLimits;
};

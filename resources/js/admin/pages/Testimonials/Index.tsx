import { Head } from '@inertiajs/react';
import TestimonialManager from '@admin/features/testimonials/TestimonialManager';
import type { TestimonialIndexProps } from '@admin/features/testimonials/types';

export default function TestimonialIndex(props: TestimonialIndexProps) {
    return (
        <>
            <Head title="Testimonials" />
            <TestimonialManager {...props} />
        </>
    );
}

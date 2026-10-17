import type { IconName } from '@/shared/ui';

// Sample content for the static design phase; replaced by Inertia props later.

export type ValueProp = {
    icon: IconName;
    title: string;
    description: string;
};

export type PromoPerk = {
    icon: IconName;
    title: string;
    description: string;
};

export type Testimonial = {
    id: number;
    quote: string;
    author: string;
};

export const VALUE_PROPS: ValueProp[] = [
    {
        icon: 'truck',
        title: 'Fast Delivery',
        description: 'Delivered to your door',
    },
    {
        icon: 'shield',
        title: 'Cash on Delivery',
        description: 'Pay when you receive',
    },
    {
        icon: 'refresh',
        title: 'Easy Returns',
        description: 'Hassle-free return policy',
    },
    {
        icon: 'support',
        title: 'Customer Support',
        description: 'Here to help you',
    },
];

export const PROMO_PERKS: PromoPerk[] = [
    {
        icon: 'shield',
        title: 'Premium Quality',
        description: 'Trusted products',
    },
    { icon: 'truck', title: 'Fast Delivery', description: 'To your door' },
    { icon: 'refresh', title: 'Easy Returns', description: 'Hassle-free' },
];

export const TESTIMONIALS: Testimonial[] = [
    {
        id: 1,
        quote: 'Amazing products and fast delivery! This is my go-to store for all my needs.',
        author: 'John D.',
    },
    {
        id: 2,
        quote: 'Great quality at affordable prices. The customer support is also very responsive.',
        author: 'Sarah M.',
    },
    {
        id: 3,
        quote: 'Very happy with my purchase. Highly recommend to everyone!',
        author: 'Michael T.',
    },
];

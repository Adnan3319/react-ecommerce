import type { ProductCardData } from '@/storefront/features/catalog/types';
import type { Category } from '@/storefront/config/site';

/** One entry of `Services\Testimonial\TestimonialService::activeForStorefront()`. */
export type ApiTestimonial = {
    id: number;
    customer_name: string;
    text: string;
    rating: number;
    source: string;
    source_label: string;
};

/** A top-level category with its sub-categories, as the shared `categories` prop sends it. */
export type HomeCategory = Category & { children: Category[] };

export type DeliveryZone = {
    key: string;
    label: string;
    charge: number;
};

/** One entry of `categoryRails`: a root category and its newest in-stock products. */
export type CategoryRail = {
    category: { name: string; slug: string };
    products: ProductCardData[];
};

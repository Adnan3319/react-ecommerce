export const TABS = [
    { id: 'basic', label: 'Basic info' },
    { id: 'images', label: 'Images' },
    { id: 'price', label: 'Price & stock' },
    { id: 'seo', label: 'SEO' },
] as const;

export type TabId = (typeof TABS)[number]['id'];

/** Mirrors the limits of SaveProductRequest. */
export const LIMITS = {
    shortDescription: 300,
    metaTitle: 60,
    metaDescription: 160,
} as const;

export const STOCK_FILTER_OPTIONS = [
    { value: 'in', label: 'In stock' },
    { value: 'low', label: 'Low stock' },
    { value: 'out', label: 'Out of stock' },
] as const;

export const FEATURED_FILTER_OPTIONS = [
    { value: '1', label: 'Featured' },
    { value: '0', label: 'Not featured' },
] as const;

/** Select value of "no filter" in the product list filters. */
export const FILTER_ALL = 'all';

/** Row-level `imageKey` of the main image (it keeps its id when replaced). */
export const MAIN_IMAGE_KEY = 'main';

export const PRODUCT_PATH_PREFIX = '/products/';

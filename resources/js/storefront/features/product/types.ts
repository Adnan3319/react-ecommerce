import type { Seo } from '@/shared/types';

export type ProductSummary = {
    id: number;
    slug: string;
    categorySlug: string;
    name: string;
    /** Integer minor units. */
    price: number;
    /** Integer minor units; shown struck through when higher than price. */
    comparePrice: number | null;
    /** Null until the product has reviews. */
    rating: number | null;
    reviewCount: number;
    image: string | null;
};

export type ProductDetail = ProductSummary & {
    images: string[];
    description: string;
};

/** Props of the product page (ProductPageService + Storefront\ProductController). Amounts are minor units. */

export type PageImage = {
    id: number;
    url: string | null;
    thumb_url: string | null;
    srcset: string | null;
};

export type PageHead = {
    id: number;
    name: string;
    values: { id: number; value: string }[];
};

export type StockState = 'in' | 'low' | 'out';

export type PageVariant = {
    id: number;
    /** One value id per head, in head order. */
    value_ids: number[];
    label: string | null;
    sku: string;
    price: number;
    sale_price: number | null;
    effective_price: number;
    discount_percent: number | null;
    stock: number;
    stock_state: StockState;
    image_id: number | null;
};

export type PageProduct = {
    id: number;
    name: string;
    slug: string;
    short_description: string | null;
    description: string | null;
    has_variants: boolean;
    images: PageImage[];
    heads: PageHead[];
    variants: PageVariant[];
};

export type DeliveryZone = {
    key: string;
    label: string;
    charge: number;
};

export type ProductPageProps = {
    product: PageProduct;
    category: { trail: { slug: string; label: string }[] };
    deliveryZones: DeliveryZone[];
    maxQty: number;
    seo: Seo;
};

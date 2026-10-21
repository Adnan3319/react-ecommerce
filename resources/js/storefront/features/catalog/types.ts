import type { PageHead, PageVariant } from '@/storefront/types/variants';

/** One entry of `Http\Resources\Storefront\ProductCardResource`. Prices are minor units. */
export type ProductCardData = {
    id: number;
    name: string;
    slug: string;
    thumb: string | null;
    srcset: string | null;
    price: number | null;
    sale_price: number | null;
    discount_percent: number | null;
    /** "3 sizes · 2 colors"; null for a simple product. */
    options_summary: string | null;
    in_stock: boolean;
    is_new: boolean;
    has_variants: boolean;
    /** Set for simple products only: the variant the card adds directly. */
    default_variant_id: number | null;
};

export type QuickAddVariant = PageVariant & { thumb: string | null };

/** Data of `GET /products/{slug}/options`. */
export type QuickAddOptions = {
    id: number;
    name: string;
    slug: string;
    thumb: string | null;
    heads: PageHead[];
    variants: QuickAddVariant[];
};

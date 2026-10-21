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

export type ListingMode = 'category' | 'search';

export type ListingCategory = {
    name: string;
    slug: string;
    description: string | null;
    parent: { name: string; slug: string } | null;
};

export type SortValue = 'newest' | 'price_asc' | 'price_desc';

export type SortOption = { value: SortValue; label: string };

export type AvailableValue = { key: string; value: string; count: number };

export type AvailableHead = {
    key: string;
    name: string;
    values: AvailableValue[];
};

/** `Services\Storefront\CatalogService::listing()` filters. Prices are taka strings. */
export type ListingFilters = {
    available: {
        price: { min: string | null; max: string | null };
        heads: AvailableHead[];
    };
    active: {
        q: string | null;
        category: string | null;
        min: string | null;
        max: string | null;
        in_stock: boolean;
        sort: SortValue;
        f: Record<string, string[]>;
    };
};

export type ListingProducts = {
    data: ProductCardData[];
    meta: {
        current_page: number;
        last_page: number;
        total: number;
    };
};

/** Props of `Categories/Show` and `Search` (`Http\Resources\Storefront\ListingProps`). */
export type ListingPageProps = {
    category: ListingCategory | null;
    products: ListingProducts;
    filters: ListingFilters;
    sortOptions: SortOption[];
};

import type { ActiveStatus, Paginated, StatusOption } from '@/shared/types';

export type StockState = 'in' | 'low' | 'out';

export type ProductListItem = {
    id: number;
    name: string;
    thumb_url: string | null;
    category: string | null;
    price: string | null;
    total_stock: number;
    stock_state: StockState;
    status: ActiveStatus;
    is_featured: boolean;
};

export type CategoryNode = {
    id: number;
    name: string;
    children: { id: number; name: string }[];
};

export type ProductListFilters = {
    search: string | null;
    status: ActiveStatus | null;
    per_page: number;
    category_id: number | null;
    featured: boolean | null;
    stock: StockState | null;
};

export type ProductIndexProps = {
    products: Paginated<ProductListItem>;
    filters: ProductListFilters;
    statuses: StatusOption[];
    categoryTree: CategoryNode[];
};

/** `ProductFormResource` (prices are taka strings). */
export type ProductDetail = {
    id: number;
    category_id: number;
    name: string;
    slug: string;
    short_description: string | null;
    description: string | null;
    status: ActiveStatus;
    is_featured: boolean;
    meta_title: string | null;
    meta_description: string | null;
    images: { id: number; url: string; thumb_url: string | null; is_main: boolean; sort_order: number }[];
    has_variants: boolean;
    sku: string | null;
    price: string | null;
    sale_price: string | null;
    stock: number;
    heads: { id: number; name: string; values: { id: number; value: string }[] }[];
    variants: {
        id: number;
        values: string[];
        sku: string;
        price: string | null;
        sale_price: string | null;
        stock: number;
        image_ref: string | null;
        status: ActiveStatus;
    }[];
};

export type ProductFormProps = {
    categoryTree: CategoryNode[];
    galleryMax: number;
    maxVariantHeads: number;
    maxVariants: number;
    statuses: StatusOption[];
    product?: ProductDetail;
};

/** A stored image (`id`) or a file picked in this session (`file`); `url` is what the tile shows. */
export type ImageItem = {
    key: string;
    id: number | null;
    url: string;
    file: File | null;
};

export type ValueChip = {
    key: string;
    id: number | null;
    value: string;
};

export type HeadState = {
    key: string;
    id: number | null;
    name: string;
    values: ValueChip[];
};

export type VariantRow = {
    /** Chip keys in head order joined by "|": the identity of the combination. */
    key: string;
    valueKeys: string[];
    id: number | null;
    sku: string;
    price: string;
    sale_price: string;
    stock: string;
    /** Key of the picked image (`main` or a gallery item key), null for none. */
    imageKey: string | null;
    status: ActiveStatus;
};

export type ProductFormData = {
    name: string;
    slug: string;
    slugTouched: boolean;
    short_description: string;
    description: string;
    status: ActiveStatus;
    is_featured: boolean;
    category_id: string;
    main: ImageItem | null;
    gallery: ImageItem[];
    has_variants: boolean;
    price: string;
    sale_price: string;
    stock: string;
    sku: string;
    heads: HeadState[];
    rows: VariantRow[];
    /** Combination keys the admin removed; they stay removed until "Add missing combinations". */
    removedRows: string[];
    meta_title: string;
    meta_description: string;
};

export type FormErrors = Partial<Record<string, string>>;

/** What every tab receives from the form shell. */
export type FormApi = {
    data: ProductFormData;
    errors: FormErrors;
    set: <K extends keyof ProductFormData>(key: K, value: ProductFormData[K]) => void;
    update: (updater: (previous: ProductFormData) => ProductFormData) => void;
};

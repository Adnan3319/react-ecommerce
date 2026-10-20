import { STATUS_ACTIVE } from '@/shared/constants/status';
import { MAIN_IMAGE_KEY, TABS } from './constants';
import type { TabId } from './constants';
import type {
    FormErrors,
    HeadState,
    ImageItem,
    ProductDetail,
    ProductFormData,
    VariantRow,
} from './types';
import { missingKeys, rowKey } from './variant-matrix';

const PERCENT = 100;

/** Prefixes of the server error keys that belong to each tab. */
const TAB_ERROR_PREFIXES: Record<TabId, string[]> = {
    basic: [
        'name',
        'slug',
        'short_description',
        'description',
        'status',
        'is_featured',
        'category_id',
    ],
    images: ['main_image', 'gallery', 'gallery_keep'],
    price: [
        'has_variants',
        'price',
        'sale_price',
        'stock',
        'sku',
        'heads',
        'variants',
    ],
    seo: ['meta_title', 'meta_description'],
};

export function newKey(): string {
    return `n-${crypto.randomUUID()}`;
}

export function slugify(text: string): string {
    return text
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
}

/** "-18" style percentage of a sale price below the price; null when there is no valid discount. */
export function discountPercent(
    price: string,
    salePrice: string,
): number | null {
    const full = Number(price);
    const sale = Number(salePrice);

    if (!(full > 0) || !(sale > 0) || sale >= full) {
        return null;
    }

    return Math.round(((full - sale) / full) * PERCENT);
}

function toImageItem(image: ProductDetail['images'][number]): ImageItem {
    return {
        key: `i-${image.id}`,
        id: image.id,
        url: image.thumb_url ?? image.url,
        file: null,
    };
}

function toHeads(product: ProductDetail): HeadState[] {
    return product.heads.map((head) => ({
        key: `h-${head.id}`,
        id: head.id,
        name: head.name,
        values: head.values.map((value) => ({
            key: `v-${value.id}`,
            id: value.id,
            value: value.value,
        })),
    }));
}

function toRows(
    product: ProductDetail,
    heads: HeadState[],
    images: ImageItem[],
): VariantRow[] {
    return product.variants.map((variant) => {
        const valueKeys = variant.values.map(
            (text, index) =>
                heads[index]?.values.find(
                    (chip) => chip.value.toLowerCase() === text.toLowerCase(),
                )?.key ?? text,
        );
        const image = images.find(
            (item) => item.id !== null && String(item.id) === variant.image_ref,
        );

        return {
            key: rowKey(valueKeys),
            valueKeys,
            id: variant.id,
            sku: variant.sku,
            price: variant.price ?? '',
            sale_price: variant.sale_price ?? '',
            stock: String(variant.stock),
            imageKey: image?.key ?? null,
            status: variant.status,
        };
    });
}

export function initialFormState(product?: ProductDetail): ProductFormData {
    if (!product) {
        return {
            name: '',
            slug: '',
            slugTouched: false,
            short_description: '',
            description: '',
            status: STATUS_ACTIVE,
            is_featured: false,
            category_id: '',
            main: null,
            gallery: [],
            has_variants: false,
            price: '',
            sale_price: '',
            stock: '0',
            sku: '',
            heads: [],
            rows: [],
            removedRows: [],
            meta_title: '',
            meta_description: '',
        };
    }

    const stored = product.images.map(toImageItem);
    const mainImage = product.images.find((image) => image.is_main);
    const main = mainImage
        ? { ...toImageItem(mainImage), key: MAIN_IMAGE_KEY }
        : null;
    const gallery = stored.filter((item) => item.id !== mainImage?.id);
    const heads = toHeads(product);
    const rows = toRows(product, heads, main ? [main, ...gallery] : gallery);

    return {
        name: product.name,
        slug: product.slug,
        slugTouched: true,
        short_description: product.short_description ?? '',
        description: product.description ?? '',
        status: product.status,
        is_featured: product.is_featured,
        category_id: String(product.category_id),
        main,
        gallery,
        has_variants: product.has_variants,
        price: product.price ?? '',
        sale_price: product.sale_price ?? '',
        stock: String(product.stock),
        sku: product.sku ?? '',
        heads,
        rows,
        removedRows: missingKeys(heads, rows),
        meta_title: product.meta_title ?? '',
        meta_description: product.meta_description ?? '',
    };
}

/** Money typed in taka: thousands separators and spaces are dropped; the backend validates the rest. */
function money(value: string): string {
    return value.replace(/[,\s]/g, '');
}

/**
 * The step 02 request payload (see doc/product/02-admin-backend.md). Variants point to images by the
 * stored id, or `new:main` / `new:<index among the new gallery files>`.
 */
export function toPayload(
    data: ProductFormData,
    isEdit: boolean,
): Record<string, unknown> {
    const newFiles = data.gallery.filter((item) => item.file !== null);
    const imageRef = (key: string | null): string => {
        if (key === MAIN_IMAGE_KEY) {
            return data.main?.id !== null && data.main?.id !== undefined
                ? String(data.main.id)
                : 'new:main';
        }

        const item = data.gallery.find((candidate) => candidate.key === key);

        if (!item) {
            return '';
        }

        return item.id !== null
            ? String(item.id)
            : `new:${newFiles.indexOf(item)}`;
    };
    const headOf = (chipKey: string) =>
        data.heads
            .flatMap((head) => head.values)
            .find((chip) => chip.key === chipKey);

    const payload: Record<string, unknown> = {
        name: data.name,
        slug: data.slug,
        short_description: data.short_description,
        description: data.description,
        status: data.status,
        is_featured: data.is_featured ? 1 : 0,
        category_id: data.category_id,
        meta_title: data.meta_title,
        meta_description: data.meta_description,
        has_variants: data.has_variants ? 1 : 0,
        gallery: newFiles.map((item) => item.file),
        gallery_keep: data.gallery
            .filter((item) => item.id !== null)
            .map((item) => item.id),
        ...(data.main?.file ? { main_image: data.main.file } : {}),
        ...(isEdit ? { _method: 'put' } : {}),
    };

    if (!data.has_variants) {
        return {
            ...payload,
            price: money(data.price),
            sale_price: money(data.sale_price),
            stock: data.stock,
            sku: data.sku.trim(),
        };
    }

    return {
        ...payload,
        heads: data.heads.map((head) => ({
            id: head.id ?? '',
            name: head.name.trim(),
            values: head.values.map((chip) => ({
                id: chip.id ?? '',
                value: chip.value,
            })),
        })),
        variants: data.rows.map((row) => ({
            id: row.id ?? '',
            values: row.valueKeys.map((key) => headOf(key)?.value ?? ''),
            sku: row.sku.trim(),
            price: money(row.price),
            sale_price: money(row.sale_price),
            stock: row.stock,
            image_ref: row.imageKey === null ? '' : imageRef(row.imageKey),
            status: row.status,
        })),
    };
}

export function tabOfError(key: string): TabId {
    const match = (Object.keys(TAB_ERROR_PREFIXES) as TabId[]).find((tab) =>
        TAB_ERROR_PREFIXES[tab].some(
            (prefix) => key === prefix || key.startsWith(`${prefix}.`),
        ),
    );

    return match ?? 'basic';
}

/** Tabs that contain at least one error, in tab order. */
export function tabsWithErrors(errors: FormErrors): TabId[] {
    const failed = new Set(Object.keys(errors).map(tabOfError));

    return TABS.map((tab) => tab.id).filter((id) => failed.has(id));
}

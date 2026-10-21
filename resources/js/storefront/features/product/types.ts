import type { Seo } from '@/shared/types';
import type {
    PageHead,
    PageVariant as PickerVariant,
} from '@/storefront/types/variants';

/** Props of the product page (ProductPageService + Storefront\ProductController). Amounts are minor units. */

export type PageImage = {
    id: number;
    url: string | null;
    thumb_url: string | null;
    srcset: string | null;
};

export type PageVariant = PickerVariant & {
    label: string | null;
    sku: string;
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

import { Seo } from '@/shared/components';
import ProductView from '@/storefront/features/product/ProductView';
import type { ProductPageProps } from '@/storefront/features/product/types';

export default function Show({ seo, ...page }: ProductPageProps) {
    return (
        <>
            <Seo seo={seo} />
            <ProductView {...page} />
        </>
    );
}

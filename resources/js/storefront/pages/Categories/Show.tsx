import { Seo } from '@/shared/components';
import type { Seo as SeoData } from '@/shared/types';
import ShopListing from '@/storefront/features/catalog/ShopListing';
import type { ListingPageProps } from '@/storefront/features/catalog/types';

type ShowProps = ListingPageProps & { seo: SeoData };

export default function Show({ seo, ...listing }: ShowProps) {
    return (
        <>
            <Seo seo={seo} />
            <ShopListing mode="category" {...listing} />
        </>
    );
}

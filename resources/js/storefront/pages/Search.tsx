import { Seo } from '@/shared/components';
import type { Seo as SeoData } from '@/shared/types';
import ShopListing from '@/storefront/features/catalog/ShopListing';
import type { ListingPageProps } from '@/storefront/features/catalog/types';

type SearchProps = ListingPageProps & { seo: SeoData };

export default function Search({ seo, ...listing }: SearchProps) {
    return (
        <>
            <Seo seo={seo} />
            <ShopListing mode="search" {...listing} />
        </>
    );
}

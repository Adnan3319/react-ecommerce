import Seo from '@/shared/components/Seo';
import type { Seo as SeoData } from '@/shared/types';
import PageView from '@/storefront/features/pages/PageView';
import type { StaticPage } from '@/storefront/features/pages/types';

type ShowProps = {
    page: StaticPage;
    seo: SeoData;
};

export default function Show({ page, seo }: ShowProps) {
    return (
        <>
            <Seo seo={seo} />
            <PageView page={page} />
        </>
    );
}

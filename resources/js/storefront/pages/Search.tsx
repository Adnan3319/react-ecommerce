import { Head, usePage } from '@inertiajs/react';
import { home } from '@/routes';
import { Breadcrumbs } from '@/storefront/components/Breadcrumbs';
import { findCategory } from '@/storefront/config/site';
import { SAMPLE_PRODUCTS } from '@/storefront/features/product';
import {
    SearchFilters,
    SearchResults,
    filterProducts,
    parseSearchParams,
} from '@/storefront/features/search';

export default function Search() {
    const { url } = usePage();
    const params = parseSearchParams(url);
    const products = filterProducts(SAMPLE_PRODUCTS, params);
    const isFiltered =
        params.q !== '' ||
        params.category !== null ||
        params.sort !== 'default';

    return (
        <>
            <Head title={params.q ? `Search: ${params.q}` : 'All Products'}>
                {isFiltered && <meta name="robots" content="noindex,follow" />}
            </Head>

            <div className="mx-auto max-w-7xl px-4 pt-6 pb-12 sm:px-6">
                <Breadcrumbs
                    items={[
                        { label: 'Home', href: home() },
                        {
                            label:
                                (params.category &&
                                    findCategory(params.category)?.label) ||
                                'Search',
                        },
                    ]}
                />

                <div className="mt-4 mb-6">
                    <SearchFilters params={params} />
                </div>

                <SearchResults params={params} products={products} />
            </div>
        </>
    );
}

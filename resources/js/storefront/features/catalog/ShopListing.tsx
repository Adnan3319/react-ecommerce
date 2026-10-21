import { home } from '@/routes';
import { show } from '@/routes/categories';
import { Breadcrumbs } from '@/storefront/components/Breadcrumbs';
import type { BreadcrumbItem } from '@/storefront/components/Breadcrumbs/Breadcrumbs';
import ActiveFilters, { countActiveFilters } from './ActiveFilters';
import EmptyResults from './EmptyResults';
import FilterSheet from './FilterSheet';
import FilterSidebar from './FilterSidebar';
import ListingSearchBox from './ListingSearchBox';
import Pagination from './Pagination';
import ProductGrid from './ProductGrid';
import SortSelect from './SortSelect';
import SubCategoryTabs from './SubCategoryTabs';
import type { ListingMode, ListingPageProps } from './types';
import { useCatalogFilters } from './useCatalogFilters';

type ShopListingProps = ListingPageProps & {
    /** `category`: the category comes from the route. `search`: it is an optional filter. */
    mode: ListingMode;
};

function heading(
    mode: ListingMode,
    q: string | null,
    categoryName: string | undefined,
): string {
    if (mode === 'category' && categoryName) {
        return categoryName;
    }

    return q ? `Results for “${q}”` : (categoryName ?? 'All products');
}

function breadcrumbs(
    mode: ListingMode,
    category: ListingPageProps['category'],
): BreadcrumbItem[] {
    const items: BreadcrumbItem[] = [{ label: 'Home', href: home() }];

    if (mode === 'search' || category === null) {
        return [...items, { label: 'Search' }];
    }

    if (category.parent) {
        items.push({
            label: category.parent.name,
            href: show(category.parent.slug),
        });
    }

    return [...items, { label: category.name }];
}

/**
 * The listing page of the storefront: one component for `/categories/{slug}` and `/search`, so
 * both have the same search box, tabs, filters, sorting, grid and pagination.
 */
export default function ShopListing({
    mode,
    category,
    products,
    filters,
    sortOptions,
}: ShopListingProps) {
    const { active } = filters;
    const actions = useCatalogFilters(mode, category?.slug ?? null, active);
    const total = products.meta.total;
    const narrowed =
        countActiveFilters(filters) > 0 || active.sort !== 'newest';

    return (
        <div className="mx-auto max-w-7xl px-4 pt-6 pb-12 sm:px-6">
            <Breadcrumbs items={breadcrumbs(mode, category)} />

            <div className="mt-4 flex flex-col gap-4">
                <div className="flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
                    <h1 className="text-3xl font-extrabold tracking-tight break-words text-neutral-900 sm:text-4xl">
                        {heading(mode, active.q, category?.name)}
                        <span className="ml-3 text-base font-normal text-neutral-500">
                            {total} {total === 1 ? 'product' : 'products'}
                        </span>
                    </h1>
                    <ListingSearchBox
                        q={active.q}
                        category={category?.slug ?? null}
                    />
                </div>

                <SubCategoryTabs mode={mode} category={category} q={active.q} />
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-[16rem_1fr]">
                <FilterSidebar filters={filters} actions={actions} />

                <section aria-label="Products" className="min-w-0">
                    <div className="mb-4 flex flex-col gap-3">
                        <div className="grid grid-cols-2 gap-3 lg:flex lg:items-center lg:justify-between">
                            <FilterSheet
                                filters={filters}
                                actions={actions}
                                total={total}
                                activeCount={countActiveFilters(filters)}
                            />
                            <div className="lg:ml-auto lg:w-56">
                                <SortSelect
                                    options={sortOptions}
                                    value={active.sort}
                                    onChange={actions.setSort}
                                />
                            </div>
                        </div>
                        <ActiveFilters filters={filters} actions={actions} />
                    </div>

                    {products.data.length > 0 ? (
                        <ProductGrid products={products.data} />
                    ) : (
                        <EmptyResults
                            q={active.q}
                            narrowed={narrowed}
                            onClear={actions.clearAll}
                        />
                    )}

                    <Pagination
                        current={products.meta.current_page}
                        last={products.meta.last_page}
                    />
                </section>
            </div>
        </div>
    );
}

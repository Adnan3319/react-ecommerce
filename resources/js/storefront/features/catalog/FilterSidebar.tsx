import FilterFields from './FilterFields';
import type { ListingFilters } from './types';
import type { useCatalogFilters } from './useCatalogFilters';

type FilterSidebarProps = {
    filters: ListingFilters;
    actions: ReturnType<typeof useCatalogFilters>;
};

/** Desktop filter column; every change is applied right away. */
export default function FilterSidebar({
    filters,
    actions,
}: FilterSidebarProps) {
    return (
        <aside
            aria-label="Filters"
            className="hidden h-fit rounded-card bg-white p-5 shadow-card ring-1 ring-neutral-200 lg:block"
        >
            <FilterFields
                filters={filters}
                onPrice={actions.setPrice}
                onInStock={actions.setInStock}
                onToggleValue={actions.toggleValue}
            />
        </aside>
    );
}

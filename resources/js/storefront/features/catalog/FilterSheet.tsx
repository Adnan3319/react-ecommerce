import { useState } from 'react';
import Sheet from '@/storefront/components/Sheet';
import { Button } from '@/storefront/components/ui';
import FilterFields from './FilterFields';
import type { ListingFilters } from './types';
import type { useCatalogFilters } from './useCatalogFilters';

type FilterSheetProps = {
    filters: ListingFilters;
    actions: ReturnType<typeof useCatalogFilters>;
    total: number;
    /** Number of filters in use, shown on the button. */
    activeCount: number;
};

/** Mobile bottom sheet. Changes apply live, so the button always shows the real result count. */
export default function FilterSheet({
    filters,
    actions,
    total,
    activeCount,
}: FilterSheetProps) {
    const [open, setOpen] = useState(false);

    return (
        <div className="lg:hidden">
            <Button
                variant="outline"
                size="md"
                fullWidth
                onClick={() => setOpen(true)}
            >
                Filters{activeCount > 0 ? ` (${activeCount})` : ''}
            </Button>
            <Sheet open={open} onClose={() => setOpen(false)} label="Filters">
                <div className="flex flex-col gap-5 p-5">
                    <h2 className="text-xl font-bold text-neutral-900">
                        Filters
                    </h2>
                    <FilterFields
                        filters={filters}
                        onPrice={actions.setPrice}
                        onInStock={actions.setInStock}
                        onToggleValue={actions.toggleValue}
                    />
                    <div className="sticky bottom-0 -mx-5 -mb-5 grid grid-cols-[1fr_2fr] gap-3 border-t border-neutral-200 bg-white p-5">
                        <Button
                            variant="outline"
                            size="lg"
                            onClick={actions.clearAll}
                        >
                            Clear
                        </Button>
                        <Button size="lg" onClick={() => setOpen(false)}>
                            Show {total} {total === 1 ? 'product' : 'products'}
                        </Button>
                    </div>
                </div>
            </Sheet>
        </div>
    );
}

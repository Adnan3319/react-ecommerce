import Select from '@admin/components/form/Select';
import StatusFilter from '@admin/components/common/StatusFilter';
import type { ExtraQuery } from '@admin/hooks/useListFilters';
import type { StatusOption } from '@/shared/types';
import {
    FEATURED_FILTER_OPTIONS,
    FILTER_ALL,
    STOCK_FILTER_OPTIONS,
} from '../constants';
import type { CategoryNode, ProductListFilters } from '../types';

type ProductFiltersProps = {
    filters: ProductListFilters;
    status: string;
    statuses: StatusOption[];
    categoryTree: CategoryNode[];
    onStatusChange: (status: string) => void;
    onExtraChange: (patch: ExtraQuery) => void;
};

/** A parent also selects its sub-categories (the backend includes them). */
function categoryOptions(tree: CategoryNode[]) {
    return [
        { value: FILTER_ALL, label: 'All categories' },
        ...tree.flatMap((parent) => [
            { value: String(parent.id), label: parent.name },
            ...parent.children.map((child) => ({
                value: String(child.id),
                label: `${parent.name} › ${child.name}`,
            })),
        ]),
    ];
}

const orAll = (
    options: readonly { value: string; label: string }[],
    all: string,
) => [{ value: FILTER_ALL, label: all }, ...options];

export default function ProductFilters({
    filters,
    status,
    statuses,
    categoryTree,
    onStatusChange,
    onExtraChange,
}: ProductFiltersProps) {
    const toParam = (value: string) =>
        value === FILTER_ALL ? undefined : value;

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <div className="sm:w-52" aria-label="Filter by category">
                <Select
                    options={categoryOptions(categoryTree)}
                    defaultValue={
                        filters.category_id === null
                            ? FILTER_ALL
                            : String(filters.category_id)
                    }
                    onChange={(value) =>
                        onExtraChange({ category_id: toParam(value) })
                    }
                />
            </div>
            <StatusFilter
                status={status}
                onStatusChange={onStatusChange}
                statuses={statuses}
            />
            <div className="sm:w-40" aria-label="Filter by featured">
                <Select
                    options={orAll(FEATURED_FILTER_OPTIONS, 'All products')}
                    defaultValue={
                        filters.featured === null
                            ? FILTER_ALL
                            : filters.featured
                              ? '1'
                              : '0'
                    }
                    onChange={(value) =>
                        onExtraChange({ featured: toParam(value) })
                    }
                />
            </div>
            <div className="sm:w-40" aria-label="Filter by stock">
                <Select
                    options={orAll(STOCK_FILTER_OPTIONS, 'Any stock')}
                    defaultValue={filters.stock ?? FILTER_ALL}
                    onChange={(value) =>
                        onExtraChange({ stock: toParam(value) })
                    }
                />
            </div>
        </div>
    );
}

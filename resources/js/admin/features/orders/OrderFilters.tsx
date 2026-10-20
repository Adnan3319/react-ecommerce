import Select from '@admin/components/form/Select';
import type { ExtraQuery } from '@admin/hooks/useListFilters';
import { FILTER_ALL, RANGE_OPTIONS, SOURCE_FILTER_LABEL } from './constants';
import type { OrderListFilters, SourceOption } from './types';

type OrderFiltersProps = {
    filters: OrderListFilters;
    sources: SourceOption[];
    onExtraChange: (patch: ExtraQuery) => void;
};

const toParam = (value: string) => (value === FILTER_ALL ? undefined : value);

export default function OrderFilters({
    filters,
    sources,
    onExtraChange,
}: OrderFiltersProps) {
    return (
        <div className="flex flex-col gap-3 sm:flex-row">
            <div className="sm:w-44" aria-label="Filter by date">
                <Select
                    options={[
                        { value: FILTER_ALL, label: 'Any date' },
                        ...RANGE_OPTIONS,
                    ]}
                    defaultValue={filters.range ?? FILTER_ALL}
                    onChange={(value) =>
                        onExtraChange({ range: toParam(value) })
                    }
                />
            </div>
            <div className="sm:w-44" aria-label="Filter by source">
                <Select
                    options={[
                        { value: FILTER_ALL, label: SOURCE_FILTER_LABEL },
                        ...sources,
                    ]}
                    defaultValue={filters.source ?? FILTER_ALL}
                    onChange={(value) =>
                        onExtraChange({ source: toParam(value) })
                    }
                />
            </div>
        </div>
    );
}

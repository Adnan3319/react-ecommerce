import Input from '@admin/components/form/input/InputField';
import Select from '@admin/components/form/Select';
import { STATUS_FILTER_ALL } from './constants';
import type { StatusOption } from './types';

type CategoryFiltersProps = {
    search: string;
    onSearchChange: (search: string) => void;
    status: string;
    onStatusChange: (status: string) => void;
    statuses: StatusOption[];
};

export default function CategoryFilters({
    search,
    onSearchChange,
    status,
    onStatusChange,
    statuses,
}: CategoryFiltersProps) {
    const options = [{ value: STATUS_FILTER_ALL, label: 'All statuses' }, ...statuses];

    return (
        <div className="mb-4 flex flex-col gap-3 sm:flex-row">
            <div className="sm:w-72">
                <Input
                    type="search"
                    value={search}
                    onChange={(event) => onSearchChange(event.target.value)}
                    placeholder="Search by name"
                    aria-label="Search categories by name"
                />
            </div>
            <div className="sm:w-48" aria-label="Filter by status">
                <Select
                    options={options}
                    defaultValue={status}
                    onChange={onStatusChange}
                    placeholder="All statuses"
                />
            </div>
        </div>
    );
}

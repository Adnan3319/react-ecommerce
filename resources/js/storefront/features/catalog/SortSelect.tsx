import type { SortOption, SortValue } from './types';

type SortSelectProps = {
    options: SortOption[];
    value: SortValue;
    onChange: (sort: SortValue) => void;
};

export default function SortSelect({
    options,
    value,
    onChange,
}: SortSelectProps) {
    return (
        <label className="flex items-center gap-2 text-sm text-neutral-600">
            <span className="sr-only sm:not-sr-only sm:shrink-0">Sort</span>
            <select
                value={value}
                onChange={(event) => onChange(event.target.value as SortValue)}
                className="h-11 min-w-0 flex-1 rounded-control border border-neutral-300 bg-white px-3 text-sm font-medium text-neutral-900 outline-none focus:border-accent-600 focus:ring-2 focus:ring-accent-100"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </label>
    );
}

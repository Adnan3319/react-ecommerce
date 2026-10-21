import { useState } from 'react';
import { cn } from '@/shared/lib';
import { COLOR_SWATCHES, PRICE_DEBOUNCE_MS, SIZE_ORDER } from './constants';
import type { AvailableHead, AvailableValue, ListingFilters } from './types';
import { useDebouncedCallback } from './useDebouncedCallback';

type FilterFieldsProps = {
    filters: ListingFilters;
    onPrice: (min: string, max: string) => void;
    onInStock: (inStock: boolean) => void;
    onToggleValue: (head: string, value: string) => void;
};

const CHIP =
    'inline-flex h-10 min-w-10 items-center justify-center rounded-control border px-3 text-sm font-medium transition-colors';

/** Price, option and availability controls shared by the desktop sidebar and the mobile sheet. */
export default function FilterFields({
    filters,
    onPrice,
    onInStock,
    onToggleValue,
}: FilterFieldsProps) {
    const { available, active } = filters;

    return (
        <div className="flex flex-col divide-y divide-neutral-200 [&>*]:py-5 [&>*:first-child]:pt-0 [&>*:last-child]:pb-0">
            <PriceFilter
                min={active.min ?? ''}
                max={active.max ?? ''}
                placeholderMin={available.price.min}
                placeholderMax={available.price.max}
                onChange={onPrice}
            />
            {available.heads.map((head) => (
                <HeadFilter
                    key={head.key}
                    head={head}
                    selected={active.f[head.key] ?? []}
                    onToggle={(value) => onToggleValue(head.key, value)}
                />
            ))}
            <fieldset>
                <legend className="mb-3 text-sm font-semibold text-neutral-900">
                    Availability
                </legend>
                <label className="flex cursor-pointer items-center gap-3 text-sm text-neutral-800">
                    <input
                        type="checkbox"
                        checked={active.in_stock}
                        onChange={(event) => onInStock(event.target.checked)}
                        className="size-5 rounded border-neutral-300 accent-accent-600"
                    />
                    In stock only
                </label>
            </fieldset>
        </div>
    );
}

type PriceFilterProps = {
    min: string;
    max: string;
    placeholderMin: string | null;
    placeholderMax: string | null;
    onChange: (min: string, max: string) => void;
};

function PriceFilter({
    min,
    max,
    placeholderMin,
    placeholderMax,
    onChange,
}: PriceFilterProps) {
    const [draft, setDraft] = useState({ min, max });
    const [lastProps, setLastProps] = useState({ min, max });
    const commit = useDebouncedCallback(onChange, PRICE_DEBOUNCE_MS);

    // Follow the URL when it changes from outside (Clear all, back button).
    if (lastProps.min !== min || lastProps.max !== max) {
        setLastProps({ min, max });
        setDraft({ min, max });
    }

    function update(field: 'min' | 'max', value: string): void {
        const next = { ...draft, [field]: value };

        setDraft(next);
        commit(next.min, next.max);
    }

    return (
        <fieldset>
            <legend className="mb-3 text-sm font-semibold text-neutral-900">
                Price (৳)
            </legend>
            <div className="grid grid-cols-2 gap-2">
                <input
                    type="number"
                    inputMode="numeric"
                    min={0}
                    aria-label="Minimum price"
                    placeholder={placeholderMin ?? 'Min'}
                    value={draft.min}
                    onChange={(event) => update('min', event.target.value)}
                    className="h-11 w-full rounded-control border border-neutral-300 bg-white px-3 text-sm outline-none focus:border-accent-600 focus:ring-2 focus:ring-accent-100"
                />
                <input
                    type="number"
                    inputMode="numeric"
                    min={0}
                    aria-label="Maximum price"
                    placeholder={placeholderMax ?? 'Max'}
                    value={draft.max}
                    onChange={(event) => update('max', event.target.value)}
                    className="h-11 w-full rounded-control border border-neutral-300 bg-white px-3 text-sm outline-none focus:border-accent-600 focus:ring-2 focus:ring-accent-100"
                />
            </div>
        </fieldset>
    );
}

/** Sizes sort S, M, L, XL; any other head keeps the order the backend sent. */
function orderedValues(head: AvailableHead): AvailableValue[] {
    if (!head.values.every((value) => SIZE_ORDER.includes(value.key))) {
        return head.values;
    }

    return [...head.values].sort(
        (a, b) => SIZE_ORDER.indexOf(a.key) - SIZE_ORDER.indexOf(b.key),
    );
}

type HeadFilterProps = {
    head: AvailableHead;
    selected: string[];
    onToggle: (value: string) => void;
};

function HeadFilter({ head, selected, onToggle }: HeadFilterProps) {
    const values = orderedValues(head);
    const useSwatches = values.every(
        (value) => COLOR_SWATCHES[value.key] !== undefined,
    );

    return (
        <fieldset>
            <legend className="mb-3 text-sm font-semibold text-neutral-900">
                {head.name}
            </legend>
            {useSwatches ? (
                <ul className="flex flex-col gap-2">
                    {values.map((value) => (
                        <li key={value.key}>
                            <label className="flex cursor-pointer items-center gap-3 text-sm text-neutral-800">
                                <input
                                    type="checkbox"
                                    checked={selected.includes(value.key)}
                                    onChange={() => onToggle(value.key)}
                                    className="peer sr-only"
                                />
                                <span
                                    aria-hidden="true"
                                    style={{
                                        backgroundColor:
                                            COLOR_SWATCHES[value.key],
                                    }}
                                    className="size-6 rounded-full ring-1 ring-neutral-300 ring-offset-2 peer-checked:ring-2 peer-checked:ring-accent-600 peer-focus-visible:outline-2 peer-focus-visible:outline-accent-600"
                                />
                                <span className="flex-1">{value.value}</span>
                                <span className="text-xs text-neutral-500">
                                    {value.count}
                                </span>
                            </label>
                        </li>
                    ))}
                </ul>
            ) : (
                <ul className="flex flex-wrap gap-2">
                    {values.map((value) => {
                        const isSelected = selected.includes(value.key);

                        return (
                            <li key={value.key}>
                                <button
                                    type="button"
                                    aria-pressed={isSelected}
                                    onClick={() => onToggle(value.key)}
                                    className={cn(
                                        CHIP,
                                        isSelected
                                            ? 'border-neutral-900 bg-neutral-900 text-white'
                                            : 'border-neutral-300 bg-white text-neutral-900 hover:border-neutral-500',
                                    )}
                                >
                                    {value.value}
                                </button>
                            </li>
                        );
                    })}
                </ul>
            )}
        </fieldset>
    );
}

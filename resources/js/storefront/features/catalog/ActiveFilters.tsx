import { formatMoney } from '@/shared/lib';
import { Icon } from '@/storefront/components/ui';
import type { ListingFilters } from './types';
import type { useCatalogFilters } from './useCatalogFilters';

type ActiveFiltersProps = {
    filters: ListingFilters;
    actions: ReturnType<typeof useCatalogFilters>;
};

type Chip = { key: string; label: string; onRemove: () => void };

const MINOR_PER_MAJOR = 100;

function money(taka: string): string {
    return formatMoney(Math.round(Number(taka) * MINOR_PER_MAJOR));
}

function priceLabel(min: string | null, max: string | null): string {
    if (min && max) {
        return `${money(min)}–${money(max)}`;
    }

    return min ? `From ${money(min)}` : `Up to ${money(max ?? '0')}`;
}

/** Number of filters in use (price counts once). */
export function countActiveFilters(filters: ListingFilters): number {
    const { active } = filters;
    const values = Object.values(active.f).reduce(
        (sum, list) => sum + list.length,
        0,
    );

    return (
        values + (active.min || active.max ? 1 : 0) + (active.in_stock ? 1 : 0)
    );
}

export default function ActiveFilters({
    filters,
    actions,
}: ActiveFiltersProps) {
    const { active, available } = filters;
    const chips: Chip[] = [];

    for (const head of available.heads) {
        for (const key of active.f[head.key] ?? []) {
            const value = head.values.find((item) => item.key === key);

            chips.push({
                key: `${head.key}:${key}`,
                label: `${head.name}: ${value?.value ?? key}`,
                onRemove: () => actions.toggleValue(head.key, key),
            });
        }
    }

    if (active.min || active.max) {
        chips.push({
            key: 'price',
            label: priceLabel(active.min, active.max),
            onRemove: () => actions.setPrice('', ''),
        });
    }

    if (active.in_stock) {
        chips.push({
            key: 'in-stock',
            label: 'In stock',
            onRemove: () => actions.setInStock(false),
        });
    }

    if (chips.length === 0) {
        return null;
    }

    return (
        <ul
            aria-label="Active filters"
            className="flex flex-wrap items-center gap-2"
        >
            {chips.map((chip) => (
                <li key={chip.key}>
                    <button
                        type="button"
                        onClick={chip.onRemove}
                        aria-label={`Remove filter ${chip.label}`}
                        className="inline-flex h-9 items-center gap-1.5 rounded-full border border-neutral-300 bg-white px-3 text-sm text-neutral-800 hover:border-neutral-500"
                    >
                        {chip.label}
                        <Icon name="close" size={14} />
                    </button>
                </li>
            ))}
            <li>
                <button
                    type="button"
                    onClick={actions.clearAll}
                    className="px-2 text-sm font-semibold text-accent-600 hover:text-accent-700"
                >
                    Clear all
                </button>
            </li>
        </ul>
    );
}

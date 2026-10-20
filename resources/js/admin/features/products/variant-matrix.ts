import { STATUS_ACTIVE } from '@/shared/constants/status';
import type { HeadState, VariantRow } from './types';

const KEY_SEPARATOR = '|';

export function rowKey(valueKeys: string[]): string {
    return valueKeys.join(KEY_SEPARATOR);
}

/** Every combination of the chips of the heads that have values, in head order. */
export function combinations(heads: HeadState[]): string[][] {
    const lists = heads
        .filter((head) => head.values.length > 0)
        .map((head) => head.values.map((chip) => chip.key));

    if (lists.length === 0) {
        return [];
    }

    return lists.reduce<string[][]>(
        (prefixes, list) =>
            prefixes.flatMap((prefix) => list.map((key) => [...prefix, key])),
        [[]],
    );
}

/** How many rows the heads produce (0 while no head has a value). */
export function combinationCount(heads: HeadState[]): number {
    const sizes = heads
        .map((head) => head.values.length)
        .filter((size) => size > 0);

    return sizes.length === 0
        ? 0
        : sizes.reduce((total, size) => total * size, 1);
}

function isSubset(small: string[], large: string[]): boolean {
    return (
        small.length < large.length && small.every((key) => large.includes(key))
    );
}

export function blankRow(valueKeys: string[]): VariantRow {
    return {
        key: rowKey(valueKeys),
        valueKeys,
        id: null,
        sku: '',
        price: '',
        sale_price: '',
        stock: '0',
        imageKey: null,
        status: STATUS_ACTIVE,
    };
}

/**
 * Rows for the current chips: rows that still match keep everything the admin typed (matching is by chip
 * identity, so a renamed chip keeps its rows); new combinations start blank, or copy the data of a row they
 * extend or reduce (adding the first value of a new head, removing a head); combinations the admin removed
 * stay removed.
 */
export function syncRows(
    heads: HeadState[],
    rows: VariantRow[],
    removed: string[],
): VariantRow[] {
    const existing = new Map(rows.map((row) => [row.key, row]));

    return combinations(heads)
        .filter((valueKeys) => !removed.includes(rowKey(valueKeys)))
        .map((valueKeys) => {
            const kept = existing.get(rowKey(valueKeys));

            if (kept) {
                return kept;
            }

            const donor = rows.find(
                (row) =>
                    isSubset(row.valueKeys, valueKeys) ||
                    isSubset(valueKeys, row.valueKeys),
            );

            if (!donor) {
                return blankRow(valueKeys);
            }

            return {
                ...blankRow(valueKeys),
                price: donor.price,
                sale_price: donor.sale_price,
                stock: donor.stock,
                imageKey: donor.imageKey,
                status: donor.status,
            };
        });
}

/** Combinations that exist for the chips but have no row (the admin removed them). */
export function missingKeys(heads: HeadState[], rows: VariantRow[]): string[] {
    const present = new Set(rows.map((row) => row.key));

    return combinations(heads)
        .map(rowKey)
        .filter((key) => !present.has(key));
}

/** "M / Pink": the chip texts of a row in head order. */
export function rowLabel(heads: HeadState[], row: VariantRow): string {
    const texts = new Map(
        heads.flatMap((head) =>
            head.values.map((chip) => [chip.key, chip.value] as const),
        ),
    );

    return row.valueKeys.map((key) => texts.get(key) ?? '').join(' / ');
}

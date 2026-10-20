import { newKey } from './form-state';
import type { FormApi, HeadState, VariantRow } from './types';
import { combinationCount, syncRows } from './variant-matrix';

export type ChipResult = 'added' | 'empty' | 'duplicate' | 'limit';

type Limits = { maxHeads: number; maxVariants: number };

export type BulkValues = { price: string; sale_price: string; stock: string };

function hasChip(head: HeadState, text: string, exceptKey?: string): boolean {
    const wanted = text.toLowerCase();

    return head.values.some((chip) => chip.key !== exceptKey && chip.value.toLowerCase() === wanted);
}

/**
 * Edits the variant heads, their value chips and the generated rows of the product form. Every edit
 * re-syncs the rows in the same state update, so rows never lag behind the chips.
 */
export function useVariantMatrix({ data, update }: Pick<FormApi, 'data' | 'update'>, limits: Limits) {
    const updateHeads = (edit: (heads: HeadState[]) => HeadState[]) =>
        update((previous) => {
            const heads = edit(previous.heads);

            return { ...previous, heads, rows: syncRows(heads, previous.rows, previous.removedRows) };
        });

    const updateHead = (headKey: string, edit: (head: HeadState) => HeadState) =>
        updateHeads((heads) => heads.map((head) => (head.key === headKey ? edit(head) : head)));

    const emptyHead = (): HeadState => ({ key: newKey(), id: null, name: '', values: [] });

    const setHasVariants = (enabled: boolean) =>
        update((previous) => ({
            ...previous,
            has_variants: enabled,
            heads: enabled && previous.heads.length === 0 ? [emptyHead()] : previous.heads,
        }));

    const addHead = () => updateHeads((heads) => (heads.length >= limits.maxHeads ? heads : [...heads, emptyHead()]));

    const removeHead = (headKey: string) => updateHeads((heads) => heads.filter((head) => head.key !== headKey));

    const renameHead = (headKey: string, name: string) => updateHead(headKey, (head) => ({ ...head, name }));

    const addChip = (headKey: string, raw: string): ChipResult => {
        const text = raw.trim();
        const head = data.heads.find((candidate) => candidate.key === headKey);

        if (!head || text === '') {
            return 'empty';
        }

        if (hasChip(head, text)) {
            return 'duplicate';
        }

        const grown = data.heads.map((candidate) =>
            candidate.key === headKey ? { ...candidate, values: [...candidate.values, { key: newKey(), id: null, value: text }] } : candidate,
        );

        if (combinationCount(grown) > limits.maxVariants) {
            return 'limit';
        }

        updateHeads(() => grown);

        return 'added';
    };

    const removeChip = (headKey: string, chipKey: string) =>
        updateHead(headKey, (head) => ({ ...head, values: head.values.filter((chip) => chip.key !== chipKey) }));

    /** Keeps the chip identity, so its rows (and typed prices) stay. */
    const renameChip = (headKey: string, chipKey: string, raw: string): ChipResult => {
        const text = raw.trim();
        const head = data.heads.find((candidate) => candidate.key === headKey);

        if (!head || text === '') {
            return 'empty';
        }

        if (hasChip(head, text, chipKey)) {
            return 'duplicate';
        }

        updateHead(headKey, (current) => ({
            ...current,
            values: current.values.map((chip) => (chip.key === chipKey ? { ...chip, value: text } : chip)),
        }));

        return 'added';
    };

    const updateRow = (key: string, patch: Partial<VariantRow>) =>
        update((previous) => ({
            ...previous,
            rows: previous.rows.map((row) => (row.key === key ? { ...row, ...patch } : row)),
        }));

    const removeRow = (key: string) =>
        update((previous) => ({
            ...previous,
            rows: previous.rows.filter((row) => row.key !== key),
            removedRows: [...previous.removedRows, key],
        }));

    const addMissing = () =>
        update((previous) => ({ ...previous, removedRows: [], rows: syncRows(previous.heads, previous.rows, []) }));

    const applyToAll = (values: BulkValues) =>
        update((previous) => ({
            ...previous,
            rows: previous.rows.map((row) => ({
                ...row,
                price: values.price === '' ? row.price : values.price,
                sale_price: values.sale_price === '' ? row.sale_price : values.sale_price,
                stock: values.stock === '' ? row.stock : values.stock,
            })),
        }));

    return {
        setHasVariants,
        addHead,
        removeHead,
        renameHead,
        addChip,
        removeChip,
        renameChip,
        updateRow,
        removeRow,
        addMissing,
        applyToAll,
        canAddHead: data.heads.length < limits.maxHeads,
        rowCount: combinationCount(data.heads),
    };
}

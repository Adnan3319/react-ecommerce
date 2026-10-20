import { useState } from 'react';
import type { PageHead, PageVariant } from './types';

type Selection = Record<number, number>;

/** The value of each head chosen by the first variant that is in stock (or the first variant). */
function initialSelection(heads: PageHead[], variants: PageVariant[]): Selection {
    const start = variants.find((variant) => variant.stock > 0) ?? variants[0];

    return Object.fromEntries(
        heads.map((head, index) => [head.id, start?.value_ids[index] ?? 0]),
    );
}

/**
 * Which value is picked per head, the variant that matches the picks, and which values can still be
 * chosen: a value is available when some variant with stock has it together with the other picked values.
 */
export function useVariantSelection(heads: PageHead[], variants: PageVariant[]) {
    const [selected, setSelected] = useState<Selection>(() => initialSelection(heads, variants));

    const variant =
        heads.length === 0
            ? variants[0]
            : variants.find((candidate) => heads.every((head) => candidate.value_ids.includes(selected[head.id]))) ?? variants[0];

    const isAvailable = (headId: number, valueId: number): boolean =>
        variants.some(
            (candidate) =>
                candidate.stock > 0 &&
                candidate.value_ids.includes(valueId) &&
                heads.every((head) => head.id === headId || candidate.value_ids.includes(selected[head.id])),
        );

    const select = (headId: number, valueId: number) => {
        if (isAvailable(headId, valueId)) {
            setSelected((current) => ({ ...current, [headId]: valueId }));
        }
    };

    return { selected, variant, isAvailable, select };
}

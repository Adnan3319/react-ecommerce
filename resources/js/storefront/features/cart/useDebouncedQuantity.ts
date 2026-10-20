import { useEffect, useState } from 'react';
import { QUANTITY_DEBOUNCE_MS } from './constants';

/**
 * A quantity that follows the stepper at once and is sent to the server once the visitor stops
 * clicking. `onCommit` gets a `revert` to call when the server refuses the new value.
 */
export function useDebouncedQuantity(
    serverQuantity: number,
    onCommit: (quantity: number, revert: () => void) => void,
) {
    const [quantity, setQuantity] = useState(serverQuantity);

    useEffect(() => {
        if (quantity === serverQuantity) {
            return;
        }

        const timer = setTimeout(
            () => onCommit(quantity, () => setQuantity(serverQuantity)),
            QUANTITY_DEBOUNCE_MS,
        );

        return () => clearTimeout(timer);
    }, [quantity, serverQuantity, onCommit]);

    return [quantity, setQuantity] as const;
}

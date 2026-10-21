import { useEffect, useRef } from 'react';

/** Calls `callback` once the arguments stopped changing for `delay` ms; a pending call is dropped on unmount. */
export function useDebouncedCallback<Args extends unknown[]>(
    callback: (...args: Args) => void,
    delay: number,
): (...args: Args) => void {
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const latest = useRef(callback);

    useEffect(() => {
        latest.current = callback;
    });

    useEffect(
        () => () => {
            if (timer.current !== null) {
                clearTimeout(timer.current);
            }
        },
        [],
    );

    return (...args: Args) => {
        if (timer.current !== null) {
            clearTimeout(timer.current);
        }

        timer.current = setTimeout(() => latest.current(...args), delay);
    };
}

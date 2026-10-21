import { createContext, useContext, useMemo, useState } from 'react';
import type { ReactNode } from 'react';

type MiniCartState = {
    isOpen: boolean;
    open: () => void;
    close: () => void;
};

const MiniCartContext = createContext<MiniCartState | null>(null);

/** Open/closed state of the mini cart drawer, shared by the header cart button and every add-to-cart. */
export function MiniCartProvider({ children }: { children: ReactNode }) {
    const [isOpen, setIsOpen] = useState(false);
    const value = useMemo(
        () => ({
            isOpen,
            open: () => setIsOpen(true),
            close: () => setIsOpen(false),
        }),
        [isOpen],
    );

    return <MiniCartContext value={value}>{children}</MiniCartContext>;
}

export function useMiniCart(): MiniCartState {
    const state = useContext(MiniCartContext);

    if (state === null) {
        throw new Error('useMiniCart must be used inside <MiniCartProvider>.');
    }

    return state;
}

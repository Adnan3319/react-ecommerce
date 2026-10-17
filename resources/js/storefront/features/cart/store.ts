import { useSyncExternalStore } from 'react';
import { CART_MAX_QUANTITY, CART_MIN_QUANTITY } from './constants';
import type { CartLine, CartProduct } from './types';

/**
 * Temporary client-side cart for the UI phase (localStorage). When the backend
 * cart exists, delete this file and read lines from the `cart` Inertia prop;
 * `useCart` keeps the same shape so components do not change.
 */
const STORAGE_KEY = 'storefront.cart';
const EMPTY_LINES: CartLine[] = [];

let lines: CartLine[] = EMPTY_LINES;
let isHydrated = false;
const listeners = new Set<() => void>();

function emit(): void {
    listeners.forEach((listener) => listener());
}

function persist(): void {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(lines));
    } catch {
        // Storage can be unavailable (private mode); the cart stays in memory.
    }
}

function hydrate(): void {
    isHydrated = true;

    try {
        const stored = localStorage.getItem(STORAGE_KEY);

        if (stored) {
            lines = JSON.parse(stored) as CartLine[];
            emit();
        }
    } catch {
        lines = EMPTY_LINES;
    }
}

function update(next: CartLine[]): void {
    lines = next;
    persist();
    emit();
}

function subscribe(listener: () => void): () => void {
    listeners.add(listener);

    if (!isHydrated) {
        hydrate();
    }

    return () => listeners.delete(listener);
}

const getSnapshot = (): CartLine[] => lines;
const getServerSnapshot = (): CartLine[] => EMPTY_LINES;

function clamp(quantity: number): number {
    return Math.min(Math.max(quantity, CART_MIN_QUANTITY), CART_MAX_QUANTITY);
}

export function addToCart(product: CartProduct): void {
    const existing = lines.find((line) => line.productId === product.id);

    if (existing) {
        update(
            lines.map((line) =>
                line === existing
                    ? { ...line, quantity: clamp(line.quantity + 1) }
                    : line,
            ),
        );

        return;
    }

    update([
        ...lines,
        {
            id: product.id,
            productId: product.id,
            slug: product.slug,
            name: product.name,
            image: product.image,
            unitPrice: product.price,
            quantity: CART_MIN_QUANTITY,
            maxQuantity: CART_MAX_QUANTITY,
        },
    ]);
}

export function setQuantity(lineId: number, quantity: number): void {
    update(
        lines.map((line) =>
            line.id === lineId ? { ...line, quantity: clamp(quantity) } : line,
        ),
    );
}

export function removeLine(lineId: number): void {
    update(lines.filter((line) => line.id !== lineId));
}

export function clearCart(): void {
    update(EMPTY_LINES);
}

export function useCart() {
    const current = useSyncExternalStore(
        subscribe,
        getSnapshot,
        getServerSnapshot,
    );

    return {
        lines: current,
        itemCount: current.reduce((sum, line) => sum + line.quantity, 0),
        subtotal: current.reduce(
            (sum, line) => sum + line.unitPrice * line.quantity,
            0,
        ),
        setQuantity,
        remove: removeLine,
        clear: clearCart,
    };
}

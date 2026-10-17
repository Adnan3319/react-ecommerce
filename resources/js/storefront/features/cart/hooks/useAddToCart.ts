import { useEffect, useState } from 'react';
import { addToCart } from '../store';
import type { CartProduct } from '../types';

const ADDED_FEEDBACK_MS = 2000;

export function useAddToCart(product: CartProduct) {
    const [isAdded, setIsAdded] = useState(false);

    useEffect(() => {
        if (!isAdded) {
            return;
        }

        const timer = setTimeout(() => setIsAdded(false), ADDED_FEEDBACK_MS);

        return () => clearTimeout(timer);
    }, [isAdded]);

    function add(): void {
        addToCart(product);
        setIsAdded(true);
    }

    return { isAdded, add };
}

import type { SortValue } from './types';

/** Props a filter change reloads. */
export const LISTING_PROPS: string[] = [
    'category',
    'products',
    'filters',
    'seo',
];

export const PRICE_DEBOUNCE_MS = 500;

/** Best match while a usable search term is set, otherwise Newest. */
export function defaultSort(active: {
    q: string | null;
    q_too_short: boolean;
}): SortValue {
    return active.q && !active.q_too_short ? 'best_match' : 'newest';
}

/** Option values that show as a colour swatch (lower-case name -> CSS colour). */
export const COLOR_SWATCHES: Record<string, string> = {
    black: '#111111',
    white: '#ffffff',
    red: '#dc2626',
    pink: '#ec4899',
    blue: '#2563eb',
    navy: '#1e3a8a',
    green: '#16a34a',
    yellow: '#facc15',
    orange: '#f97316',
    purple: '#9333ea',
    brown: '#92400e',
    grey: '#9ca3af',
    gray: '#9ca3af',
    beige: '#d6c3a3',
    gold: '#d4af37',
    silver: '#c0c0c0',
};

/** Clothing sizes in their natural order; heads made only of these are listed in this order. */
export const SIZE_ORDER = [
    'xxs',
    'xs',
    's',
    'm',
    'l',
    'xl',
    'xxl',
    '2xl',
    '3xl',
    '4xl',
    '5xl',
];

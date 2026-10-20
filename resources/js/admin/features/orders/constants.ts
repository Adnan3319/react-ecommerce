/** Order status values the UI treats specially (App\Enums\OrderStatus). */
export const STATUS_CANCELLED = 'cancelled';
export const STATUS_RETURNED = 'returned';

/** Moves that end the order: shown as danger actions and asking for a note (TransitionOrderRequest). */
export const ENDING_STATUSES: readonly string[] = [
    STATUS_CANCELLED,
    STATUS_RETURNED,
];

export const NOTE_MAX_LENGTH = 500;

/** Select value of "no filter" in the order list filters. */
export const FILTER_ALL = 'all';

/** Mirrors IndexOrderRequest::RANGES. */
export const RANGE_OPTIONS = [
    { value: 'today', label: 'Today' },
    { value: '7d', label: 'Last 7 days' },
    { value: '30d', label: 'Last 30 days' },
] as const;

export const COPIED_RESET_MS = 2000;

/** Dates show in the shop's timezone so every admin sees the same times. */
export const SHOP_TIME_ZONE = 'Asia/Dhaka';

export const SOURCE_FILTER_LABEL = 'Source: All';

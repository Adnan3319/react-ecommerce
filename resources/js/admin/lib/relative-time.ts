import { formatShort } from './format-date';

const MINUTE_MS = 60_000;
const HOUR_MS = 60 * MINUTE_MS;
const DAY_MS = 24 * HOUR_MS;
const RELATIVE_DAYS_LIMIT = 7;

/** "just now", "5 min ago", "3 h ago", "2 d ago"; a week or older falls back to the shop-timezone date. */
export function formatRelative(iso: string, now: number = Date.now()): string {
    const elapsed = Math.max(0, now - new Date(iso).getTime());

    if (elapsed < MINUTE_MS) {
        return 'just now';
    }

    if (elapsed < HOUR_MS) {
        return `${Math.floor(elapsed / MINUTE_MS)} min ago`;
    }

    if (elapsed < DAY_MS) {
        return `${Math.floor(elapsed / HOUR_MS)} h ago`;
    }

    if (elapsed < RELATIVE_DAYS_LIMIT * DAY_MS) {
        return `${Math.floor(elapsed / DAY_MS)} d ago`;
    }

    return formatShort(iso);
}

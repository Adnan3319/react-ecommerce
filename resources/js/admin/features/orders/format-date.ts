import { SHOP_TIME_ZONE } from './constants';

const SHORT = new Intl.DateTimeFormat('en-GB', {
    timeZone: SHOP_TIME_ZONE,
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
});

const FULL = new Intl.DateTimeFormat('en-GB', {
    timeZone: SHOP_TIME_ZONE,
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
});

/** "20 Oct, 10:42" */
export const formatShort = (iso: string): string =>
    SHORT.format(new Date(iso)).replace(' at', ',');

/** "20 Oct 2026, 10:42" */
export const formatFull = (iso: string): string =>
    FULL.format(new Date(iso)).replace(' at', ',');

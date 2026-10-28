export const POLL_INTERVAL_MS = 30_000;

/** Counts above this show as "9+" on the bell badge. */
export const BADGE_MAX = 9;

/** Props reloaded by the poll: the bell count and the sidebar badges. */
export const POLL_PROPS = ['notifications', 'admin'];

/** Props reloaded when the bell opens or the count changes while it is open. */
export const BELL_PROPS = ['notifications', 'notificationItems', 'admin'];

export const FILTER_ALL = 'all';

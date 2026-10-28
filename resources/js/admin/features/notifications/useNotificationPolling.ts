import { router, usePoll } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { POLL_INTERVAL_MS, POLL_PROPS } from './constants';

const COUNT_PREFIX = /^\(\d+\)\s/;

/**
 * Reloads only the bell count and the sidebar badges every 30 s (an Inertia partial reload, no JSON
 * endpoint). Polling stops while the tab is hidden and catches up with one reload when it is shown again.
 */
export function useNotificationPolling(): void {
    const { start, stop } = usePoll(POLL_INTERVAL_MS, { only: POLL_PROPS });
    const controls = useRef({ start, stop });

    useEffect(() => {
        controls.current = { start, stop };
    });

    useEffect(() => {
        const sync = () => {
            if (document.hidden) {
                controls.current.stop();

                return;
            }

            router.reload({ only: POLL_PROPS });
            controls.current.start();
        };

        if (document.hidden) {
            controls.current.stop();
        }

        document.addEventListener('visibilitychange', sync);

        return () => document.removeEventListener('visibilitychange', sync);
    }, []);
}

/**
 * Prefixes the browser tab title with the unread count, "(3) Orders - Shop", and keeps the prefix when
 * Inertia swaps the title on navigation.
 */
export function useUnreadTitle(unreadCount: number): void {
    useEffect(() => {
        const titleElement = document.querySelector('title');

        if (titleElement === null) {
            return;
        }

        const apply = () => {
            const base = document.title.replace(COUNT_PREFIX, '');
            const next = unreadCount > 0 ? `(${unreadCount}) ${base}` : base;

            if (document.title !== next) {
                document.title = next;
            }
        };

        apply();

        const observer = new MutationObserver(apply);
        observer.observe(titleElement, {
            childList: true,
            characterData: true,
            subtree: true,
        });

        return () => {
            observer.disconnect();
            document.title = document.title.replace(COUNT_PREFIX, '');
        };
    }, [unreadCount]);
}

import { Head } from '@inertiajs/react';
import NotificationManager from '@admin/features/notifications/NotificationManager';
import type { NotificationIndexProps } from '@admin/features/notifications/types';

export default function NotificationIndex(props: NotificationIndexProps) {
    return (
        <>
            <Head title="Notifications" />
            <NotificationManager {...props} />
        </>
    );
}

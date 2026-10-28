import { Head } from '@inertiajs/react';
import FlashMessage from '@admin/components/common/FlashMessage';
import MessageManager from '@admin/features/messages/MessageManager';
import type { MessageIndexProps } from '@admin/features/messages/types';

export default function MessageIndex(props: MessageIndexProps) {
    return (
        <>
            <Head title="Messages" />
            <FlashMessage />
            <MessageManager {...props} />
        </>
    );
}

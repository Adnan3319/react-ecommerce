import { Head } from '@inertiajs/react';
import MessageDetail from '@admin/features/messages/MessageDetail';
import type { MessageShowProps } from '@admin/features/messages/types';

export default function MessageShow(props: MessageShowProps) {
    return (
        <>
            <Head title={`Message from ${props.message.name}`} />
            <MessageDetail {...props} />
        </>
    );
}

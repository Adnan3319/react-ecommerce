import { Head } from '@inertiajs/react';
import PageManager from '@admin/features/pages/PageManager';
import type { PageIndexProps } from '@admin/features/pages/types';

export default function PagesIndex(props: PageIndexProps) {
    return (
        <>
            <Head title="Pages" />
            <PageManager {...props} />
        </>
    );
}
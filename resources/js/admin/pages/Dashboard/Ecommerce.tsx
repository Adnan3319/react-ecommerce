import { Head } from '@inertiajs/react';
import DashboardView from '@admin/features/dashboard/DashboardView';
import type { DashboardProps } from '@admin/features/dashboard/types';

export default function Ecommerce(props: DashboardProps) {
    return (
        <>
            <Head title="Dashboard" />
            <DashboardView {...props} />
        </>
    );
}

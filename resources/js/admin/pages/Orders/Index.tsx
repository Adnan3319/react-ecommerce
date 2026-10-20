import { Head } from '@inertiajs/react';
import OrderManager from '@admin/features/orders/OrderManager';
import type { OrderIndexProps } from '@admin/features/orders/types';

export default function OrderIndex(props: OrderIndexProps) {
    return (
        <>
            <Head title="Orders" />
            <OrderManager {...props} />
        </>
    );
}

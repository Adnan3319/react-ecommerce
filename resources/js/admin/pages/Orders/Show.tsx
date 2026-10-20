import { Head } from '@inertiajs/react';
import OrderDetail from '@admin/features/orders/OrderDetail';
import type { OrderShowProps } from '@admin/features/orders/types';

export default function OrderShow({ order }: OrderShowProps) {
    return (
        <>
            <Head title={order.number} />
            <OrderDetail order={order} />
        </>
    );
}

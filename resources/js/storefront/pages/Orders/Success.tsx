import { Seo } from '@/shared/components';
import type { Seo as SeoData } from '@/shared/types';
import OrderSuccess from '@/storefront/features/order/OrderSuccess';
import type { PlacedOrder } from '@/storefront/features/order/types';

type SuccessProps = {
    order: PlacedOrder;
    seo: SeoData;
};

export default function Success({ order, seo }: SuccessProps) {
    return (
        <>
            <Seo seo={seo} />
            <OrderSuccess order={order} />
        </>
    );
}

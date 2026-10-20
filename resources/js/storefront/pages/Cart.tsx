import { Seo } from '@/shared/components';
import type { Seo as SeoData } from '@/shared/types';
import CartView from '@/storefront/features/cart/CartView';
import type { CartLine } from '@/storefront/features/cart/types';
import type { DeliveryZone } from '@/storefront/features/product/types';

type CartPageProps = {
    lines: CartLine[];
    subtotal: number;
    deliveryZones: DeliveryZone[];
    seo: SeoData;
};

export default function Cart({ seo, ...cart }: CartPageProps) {
    return (
        <>
            <Seo seo={seo} />
            <CartView {...cart} />
        </>
    );
}

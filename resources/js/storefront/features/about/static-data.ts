import type { IconName } from '@/shared/ui';

export type AboutValue = {
    icon: IconName;
    title: string;
    description: string;
};

export const ABOUT_VALUES: AboutValue[] = [
    {
        icon: 'shield',
        title: 'Quality you can trust',
        description:
            'We pick every kitchenware and fashion item with care, so what you see is what you get.',
    },
    {
        icon: 'truck',
        title: 'Delivered to your door',
        description:
            'Order from your phone and we bring it to you, anywhere in Bangladesh.',
    },
    {
        icon: 'refresh',
        title: 'Cash on Delivery',
        description:
            'Pay only when your order arrives. No advance payment, no worry.',
    },
    {
        icon: 'support',
        title: 'Friendly support',
        description:
            'Questions about a product or an order? Our team is happy to help.',
    },
];

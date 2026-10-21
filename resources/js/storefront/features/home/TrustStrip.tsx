import { formatMoney } from '@/shared/lib';
import { Icon } from '@/storefront/components/ui';
import type { IconName } from '@/storefront/components/ui';
import type { DeliveryZone } from './types';

type TrustStripProps = {
    deliveryZones: DeliveryZone[];
};

type TrustItem = {
    icon: IconName;
    title: string;
    description: string;
};

const RETURN_DAYS = 7;

function deliveryDescription(zones: DeliveryZone[]): string {
    const cheapest = zones.reduce<DeliveryZone | null>(
        (best, zone) =>
            best === null || zone.charge < best.charge ? zone : best,
        null,
    );

    return cheapest
        ? `${cheapest.label} ${formatMoney(cheapest.charge)}`
        : 'To your door';
}

/** Glass feature strip; sits on top of the hero image. */
export default function TrustStrip({ deliveryZones }: TrustStripProps) {
    const items: TrustItem[] = [
        {
            icon: 'shield',
            title: 'Cash on Delivery',
            description: 'Pay when it arrives',
        },
        {
            icon: 'truck',
            title: 'Fast Delivery',
            description: deliveryDescription(deliveryZones),
        },
        {
            icon: 'refresh',
            title: 'Easy Return',
            description: `${RETURN_DAYS} days, hassle-free`,
        },
        {
            icon: 'support',
            title: 'Friendly Support',
            description: 'Call or message us',
        },
    ];

    return (
        <ul
            aria-label="Why shop with us"
            className="mx-auto flex w-full max-w-7xl [scrollbar-width:none] gap-6 overflow-x-auto border-t border-white/10 px-4 pt-3 pb-10 sm:px-6 md:pb-12 lg:justify-between [&::-webkit-scrollbar]:hidden"
        >
            {items.map((item) => (
                <li
                    key={item.title}
                    className="flex shrink-0 items-center gap-2.5"
                >
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/20 backdrop-blur">
                        <Icon name={item.icon} size={18} />
                    </span>
                    <p className="text-sm leading-tight">
                        <span className="font-semibold">{item.title}</span>
                        <span className="block text-xs text-white/60">
                            {item.description}
                        </span>
                    </p>
                </li>
            ))}
        </ul>
    );
}

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

/** Four reasons to buy, overlapping the bottom of the hero. */
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
        <section
            aria-label="Why shop with us"
            className="relative z-10 mx-auto -mt-10 max-w-7xl px-4 sm:px-6"
        >
            <ul className="grid grid-cols-2 gap-2 rounded-card bg-white p-2 shadow-xl ring-1 ring-neutral-200 lg:grid-cols-4">
                {items.map((item) => (
                    <li
                        key={item.title}
                        className="flex items-center gap-3 rounded-control bg-neutral-50 p-3"
                    >
                        <span className="flex size-10 shrink-0 items-center justify-center rounded-control bg-accent-50 text-accent-700">
                            <Icon name={item.icon} size={20} />
                        </span>
                        <div className="min-w-0">
                            <p className="text-sm font-semibold text-neutral-900">
                                {item.title}
                            </p>
                            <p className="text-xs text-neutral-600">
                                {item.description}
                            </p>
                        </div>
                    </li>
                ))}
            </ul>
        </section>
    );
}

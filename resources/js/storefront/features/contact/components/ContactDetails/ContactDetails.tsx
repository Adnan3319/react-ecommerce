import { Icon } from '@/storefront/components/ui';
import type { IconName } from '@/storefront/components/ui';
import { CONTACT_INFO } from '@/storefront/config/site';

type Detail = {
    icon: IconName;
    label: string;
    value: string;
};

const DETAILS: Detail[] = [
    { icon: 'phone', label: 'Phone', value: CONTACT_INFO.phone },
    { icon: 'mail', label: 'Email', value: CONTACT_INFO.email },
    { icon: 'mapPin', label: 'Address', value: CONTACT_INFO.address },
    { icon: 'clock', label: 'Working hours', value: CONTACT_INFO.hours },
];

export default function ContactDetails() {
    return (
        <ul className="flex flex-col gap-4">
            {DETAILS.map((detail) => (
                <li
                    key={detail.label}
                    className="flex items-start gap-4 rounded-card border border-neutral-200 bg-white p-4 shadow-card"
                >
                    <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-accent-50 text-accent-600">
                        <Icon name={detail.icon} size={22} />
                    </span>
                    <div className="min-w-0">
                        <p className="text-sm text-neutral-600">
                            {detail.label}
                        </p>
                        <p className="font-semibold break-words text-neutral-900">
                            {detail.value}
                        </p>
                    </div>
                </li>
            ))}
        </ul>
    );
}

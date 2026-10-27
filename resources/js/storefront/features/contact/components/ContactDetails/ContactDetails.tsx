import { usePage } from '@inertiajs/react';
import { Icon } from '@/storefront/components/ui';
import type { IconName } from '@/storefront/components/ui';

type Detail = {
    icon: IconName;
    label: string;
    value: string;
};

export default function ContactDetails() {
    const { company }: any = usePage().props;

    const DETAILS: Detail[] = [
        { icon: 'phone', label: 'Phone', value: company?.phone || '+880 1XXX-XXXXXX' },
        { icon: 'mail', label: 'Email', value: company?.email || 'hello@checkit.example' },
        { icon: 'mapPin', label: 'Address', value: company?.address || 'Dhaka, Bangladesh' },
        { icon: 'clock', label: 'Working hours', value: company?.working_hours || 'Saturday to Thursday, 10:00 AM to 8:00 PM' },
    ];

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
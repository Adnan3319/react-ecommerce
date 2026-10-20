import { useEffect, useState } from 'react';
import Button from '@admin/components/ui/button/Button';
import { COPIED_RESET_MS } from './constants';
import type { OrderDetailData } from './types';

function Field({ label, children }: { label: string; children: string }) {
    return (
        <div>
            <dt className="text-xs text-gray-500">{label}</dt>
            <dd className="font-medium text-gray-900 dark:text-white/90">
                {children}
            </dd>
        </div>
    );
}

export default function CustomerCard({ order }: { order: OrderDetailData }) {
    const { customer } = order;
    const [copied, setCopied] = useState(false);

    useEffect(() => {
        if (!copied) {
            return;
        }

        const timer = setTimeout(() => setCopied(false), COPIED_RESET_MS);

        return () => clearTimeout(timer);
    }, [copied]);

    const copyPhone = () => {
        void navigator.clipboard
            .writeText(customer.phone)
            .then(() => setCopied(true));
    };

    return (
        <>
            <h2 className="mb-4 text-lg font-semibold text-gray-900 dark:text-white/90">
                Customer
            </h2>
            <dl className="space-y-3 text-sm">
                <Field label="Name">{customer.name}</Field>
                <Field label="Phone">{customer.phone}</Field>
                <Field label="Address">{customer.address}</Field>
                <Field label="Delivery area">{order.delivery_zone_label}</Field>
                {order.customer_note && (
                    <Field label="Customer note">{order.customer_note}</Field>
                )}
            </dl>
            <div className="mt-4 grid grid-cols-2 gap-3">
                <a
                    href={`tel:${customer.phone}`}
                    className="bg-success-50 text-success-600 hover:bg-success-100 dark:bg-success-500/15 inline-flex items-center justify-center rounded-lg px-4 py-3 text-sm font-medium"
                >
                    Call
                </a>
                <Button variant="secondary" size="sm" onClick={copyPhone}>
                    {copied ? 'Copied' : 'Copy'}
                </Button>
            </div>
        </>
    );
}

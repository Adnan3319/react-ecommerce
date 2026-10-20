import { router } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmDialog from '@admin/components/common/ConfirmDialog';
import Button from '@admin/components/ui/button/Button';
import { block, unblock } from '@/routes/admin/customers';
import type { OrderCustomer } from './types';

export default function BlockPhoneButton({ customer }: { customer: OrderCustomer }) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const submit = () => {
        const options = {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setOpen(false);
            },
        };

        if (customer.is_blocked) {
            router.delete(unblock(customer.id).url, options);
        } else {
            router.post(block(customer.id).url, {}, options);
        }
    };

    const verb = customer.is_blocked ? 'Unblock' : 'Block';

    return (
        <>
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">Danger zone</h2>
            <p className="mb-4 text-sm text-gray-500">
                {customer.is_blocked
                    ? 'This phone cannot place new orders.'
                    : 'Block this phone from placing new orders.'}
            </p>
            <Button
                variant="outline"
                size="sm"
                className="w-full text-error-600 ring-error-300 hover:bg-error-50 dark:text-error-500"
                onClick={() => setOpen(true)}
            >
                {verb} {customer.phone}
            </Button>

            <ConfirmDialog
                isOpen={open}
                title={`${verb} ${customer.phone}`}
                message={
                    customer.is_blocked
                        ? 'This phone will be able to order again.'
                        : 'Orders from this phone number will be refused. Existing orders are not affected.'
                }
                confirmLabel={processing ? 'Saving...' : verb}
                processing={processing}
                confirmVariant={customer.is_blocked ? 'primary' : 'danger'}
                onConfirm={submit}
                onCancel={() => setOpen(false)}
            />
        </>
    );
}

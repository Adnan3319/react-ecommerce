import { router } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmDialog from '@admin/components/common/ConfirmDialog';
import TextArea from '@admin/components/form/input/TextArea';
import Button from '@admin/components/ui/button/Button';
import { transition } from '@/routes/admin/orders';
import {
    ENDING_STATUSES,
    NOTE_MAX_LENGTH,
    STATUS_CANCELLED,
} from './constants';
import type { OrderDetailData, OrderStatusOption } from './types';

type Errors = { to?: string; note?: string };

/** Buttons for every move the backend allows: normal steps are primary, ending moves ask for a note first. */
export default function StatusActions({ order }: { order: OrderDetailData }) {
    const [target, setTarget] = useState<OrderStatusOption | null>(null);
    const [note, setNote] = useState('');
    const [errors, setErrors] = useState<Errors>({});
    const [processing, setProcessing] = useState(false);

    const move = (status: OrderStatusOption, reason?: string) =>
        router.post(
            transition(order.id).url,
            { to: status.value, note: reason },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onError: (received) => setErrors(received),
                onSuccess: () => close(),
                onFinish: () => setProcessing(false),
            },
        );

    const close = () => {
        setTarget(null);
        setNote('');
        setErrors({});
    };

    const steps = order.allowed_next.filter(
        (status) => !ENDING_STATUSES.includes(status.value),
    );
    const endings = order.allowed_next.filter((status) =>
        ENDING_STATUSES.includes(status.value),
    );

    return (
        <div className="flex flex-wrap items-center gap-3">
            {errors.to && !target && (
                <p className="text-error-600 text-sm">{errors.to}</p>
            )}

            {endings.map((status) => (
                <Button
                    key={status.value}
                    variant="outline"
                    size="sm"
                    className="text-error-600 ring-error-300 hover:bg-error-50 dark:text-error-500"
                    disabled={processing}
                    onClick={() => setTarget(status)}
                >
                    {status.value === STATUS_CANCELLED
                        ? 'Cancel order'
                        : `Mark as ${status.label.toLowerCase()}`}
                </Button>
            ))}

            {steps.map((status) => (
                <Button
                    key={status.value}
                    size="sm"
                    disabled={processing}
                    onClick={() => move(status)}
                >
                    Mark as {status.label.toLowerCase()}
                </Button>
            ))}

            <ConfirmDialog
                isOpen={target !== null}
                title={
                    target
                        ? `Mark ${order.number} as ${target.label.toLowerCase()}`
                        : ''
                }
                message={
                    <>
                        <p className="mb-3">
                            {target?.value === STATUS_CANCELLED
                                ? 'The stock of this order goes back to the shop.'
                                : 'The stock of this order goes back to the shop. Payment stays as it is.'}
                        </p>
                        <TextArea
                            value={note}
                            onChange={setNote}
                            rows={3}
                            placeholder="Why? (required)"
                            error={Boolean(errors.note ?? errors.to)}
                            hint={errors.note ?? errors.to}
                        />
                        <p className="mt-1 text-xs text-gray-500">
                            {note.length}/{NOTE_MAX_LENGTH}
                        </p>
                    </>
                }
                confirmLabel={processing ? 'Saving...' : (target?.label ?? '')}
                processing={processing}
                onConfirm={() => target && move(target, note)}
                onCancel={close}
            />
        </div>
    );
}

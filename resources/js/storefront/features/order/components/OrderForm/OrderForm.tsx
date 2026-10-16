import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { formatMoney } from '@/shared/lib';
import { Button, Input, Textarea } from '@/shared/ui';
import { validateOrder } from '../../schemas';
import type { OrderValues } from '../../schemas';
import { QuantityStepper } from '../QuantityStepper';

export const ORDER_FORM_ID = 'order-form';

type OrderFormProps = {
    productName: string;
    /** Integer minor units. */
    unitPrice: number;
};

export default function OrderForm({ productName, unitPrice }: OrderFormProps) {
    const form = useForm<OrderValues>({
        name: '',
        phone: '',
        address: '',
        quantity: 1,
    });
    const [isPreviewShown, setIsPreviewShown] = useState(false);

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const errors = validateOrder(form.data);
        form.clearErrors();

        if (Object.keys(errors).length > 0) {
            form.setError(errors);
            setIsPreviewShown(false);

            return;
        }

        // Order placement is not connected to a backend yet.
        setIsPreviewShown(true);
    }

    return (
        <form
            id={ORDER_FORM_ID}
            onSubmit={handleSubmit}
            noValidate
            aria-label={`Order ${productName}`}
            className="flex scroll-mt-40 flex-col gap-4 rounded-card border border-neutral-200 bg-white p-5 shadow-card"
        >
            <h2 className="text-lg font-bold tracking-tight text-neutral-900">
                Order now
            </h2>

            <Input
                label="Your name"
                name="name"
                autoComplete="name"
                value={form.data.name}
                error={form.errors.name}
                onChange={(event) => form.setData('name', event.target.value)}
            />
            <Input
                label="Mobile number"
                name="phone"
                type="tel"
                inputMode="numeric"
                autoComplete="tel"
                placeholder="01XXXXXXXXX"
                value={form.data.phone}
                error={form.errors.phone}
                onChange={(event) => form.setData('phone', event.target.value)}
            />
            <Textarea
                label="Delivery address"
                name="address"
                autoComplete="street-address"
                value={form.data.address}
                error={form.errors.address}
                onChange={(event) =>
                    form.setData('address', event.target.value)
                }
            />

            <QuantityStepper
                value={form.data.quantity}
                onChange={(quantity) => form.setData('quantity', quantity)}
            />

            <dl className="flex items-center justify-between border-t border-neutral-200 pt-4 text-sm">
                <dt className="text-neutral-600">Subtotal</dt>
                <dd className="text-lg font-bold text-neutral-900">
                    {formatMoney(unitPrice * form.data.quantity)}
                </dd>
            </dl>
            <p className="-mt-2 text-xs text-neutral-500">
                Delivery charge is added when your order is confirmed. Payment
                is Cash on Delivery.
            </p>

            <Button type="submit" size="lg" fullWidth>
                Confirm Order
            </Button>

            {isPreviewShown && (
                <p
                    role="status"
                    className="rounded-control bg-accent-50 p-3 text-sm text-accent-700"
                >
                    Your details look good. Online ordering is not live yet, so
                    this order was not placed.
                </p>
            )}
        </form>
    );
}

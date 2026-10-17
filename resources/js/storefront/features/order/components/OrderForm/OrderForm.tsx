import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Button, Icon, Input, QuantityStepper, Textarea } from '@/shared/ui';
import { MAX_QUANTITY, MIN_QUANTITY, validateOrder } from '../../schemas';
import type { OrderValues } from '../../schemas';
import { OrderTotals } from '../OrderTotals';

export const ORDER_FORM_ID = 'order-form';

type OrderFormProps = {
    /** Integer minor units. */
    deliveryCharge: number;
} & (
    | {
          source: 'buy_now';
          productId: number;
          productName: string;
          /** Integer minor units. */
          unitPrice: number;
      }
    | {
          source: 'cart';
          /** Integer minor units. */
          subtotal: number;
      }
);

/** One form for both flows: single product (`buy_now`) and the whole cart. */
export default function OrderForm(props: OrderFormProps) {
    const isBuyNow = props.source === 'buy_now';
    const form = useForm<OrderValues>({
        source: props.source,
        productId: isBuyNow ? props.productId : null,
        name: '',
        phone: '',
        address: '',
        quantity: MIN_QUANTITY,
    });
    const [isPreviewShown, setIsPreviewShown] = useState(false);

    const subtotal = isBuyNow
        ? props.unitPrice * form.data.quantity
        : props.subtotal;

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
            aria-label={isBuyNow ? `Order ${props.productName}` : 'Place order'}
            className="flex scroll-mt-40 flex-col gap-4 rounded-card border border-neutral-200 bg-white p-5 shadow-card"
        >
            <h2 className="text-lg font-bold tracking-tight text-neutral-900">
                {isBuyNow ? 'Order now' : 'Delivery details'}
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

            {isBuyNow && (
                <QuantityStepper
                    label="Quantity"
                    value={form.data.quantity}
                    min={MIN_QUANTITY}
                    max={MAX_QUANTITY}
                    onChange={(quantity) => form.setData('quantity', quantity)}
                />
            )}

            <OrderTotals
                subtotal={subtotal}
                deliveryCharge={props.deliveryCharge}
            />

            <Button type="submit" size="lg" fullWidth>
                <Icon name="bolt" size={18} />
                Confirm Order
            </Button>
            <p className="-mt-1 text-center text-xs text-neutral-500">
                Payment is Cash on Delivery.
            </p>

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

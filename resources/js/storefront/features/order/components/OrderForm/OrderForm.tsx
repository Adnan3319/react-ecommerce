import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { formatMoney } from '@/shared/lib';
import { Button, Icon, Input, QuantityStepper, Textarea } from '@/storefront/components/ui';
import { MAX_QUANTITY, MIN_QUANTITY, validateOrder } from '../../schemas';
import type { OrderValues } from '../../schemas';
import { OrderTotals } from '../OrderTotals';

export const ORDER_FORM_ID = 'order-form';

type DeliveryZone = 'inside_dhaka' | 'outside_dhaka';

type OrderFormProps = {
    /** Integer minor units. Charge for Inside Dhaka (and the only charge when no outside charge is given). */
    deliveryCharge: number;
    /** Integer minor units. When set, Inside / Outside Dhaka radio options are shown. */
    outsideDhakaDeliveryCharge?: number;
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
    const [zone, setZone] = useState<DeliveryZone>('inside_dhaka');

    const zones: { value: DeliveryZone; label: string; charge: number }[] =
        props.outsideDhakaDeliveryCharge === undefined
            ? []
            : [
                  {
                      value: 'inside_dhaka',
                      label: 'Inside Dhaka',
                      charge: props.deliveryCharge,
                  },
                  {
                      value: 'outside_dhaka',
                      label: 'Outside Dhaka',
                      charge: props.outsideDhakaDeliveryCharge,
                  },
              ];

    const deliveryCharge =
        zone === 'outside_dhaka' && props.outsideDhakaDeliveryCharge !== undefined
            ? props.outsideDhakaDeliveryCharge
            : props.deliveryCharge;

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
            className="flex scroll-mt-40 flex-col gap-5 rounded-[28px] bg-white/70 p-6 shadow-[0_8px_30px_rgba(15,23,42,0.06)] ring-1 ring-white/80 backdrop-blur-md transition-all sm:p-7"
        >
            {/* Header, same rhythm as the "Information / Overview" box */}
            <div className="flex items-center justify-between text-sm">
                <span className="text-neutral-400">
                    {isBuyNow ? 'Express checkout' : 'Delivery details'}
                </span>
                <span className="font-medium text-neutral-800">Order</span>
            </div>

            {/* Highlighted title, like the "4.1" rating */}
            <div>
                <h2 className="inline-block bg-sky-200/70 px-1.5 text-3xl font-extrabold leading-tight tracking-tight text-neutral-900">
                    {isBuyNow ? 'Order now' : 'Place order'}
                </h2>
                <p className="mt-2 text-xs text-neutral-400">
                    Please fill in your details to complete the order
                </p>
            </div>

            {/* Inputs styled like the review bubbles */}
            <div className="space-y-4 [&_input[type=text]]:rounded-xl [&_input[type=text]]:border-transparent [&_input[type=text]]:bg-neutral-100/80 [&_input[type=tel]]:rounded-xl [&_input[type=tel]]:border-transparent [&_input[type=tel]]:bg-neutral-100/80 [&_textarea]:rounded-xl [&_textarea]:border-transparent [&_textarea]:bg-neutral-100/80">
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

                {zones.length > 0 && (
                    <fieldset className="flex flex-col gap-2">
                        <legend className="mb-1 text-sm font-medium text-neutral-700">
                            Delivery area
                        </legend>

                        {zones.map((item) => (
                            <label
                                key={item.value}
                                className={
                                    'flex cursor-pointer items-center gap-3 rounded-xl px-4 py-3 text-sm transition-colors ' +
                                    (zone === item.value
                                        ? 'bg-accent-50'
                                        : 'bg-neutral-100/80 hover:bg-neutral-200/60')
                                }
                            >
                                <input
                                    type="radio"
                                    name="delivery_zone"
                                    value={item.value}
                                    checked={zone === item.value}
                                    onChange={() => setZone(item.value)}
                                    className="h-4 w-4 shrink-0 cursor-pointer accent-accent-600"
                                />
                                <span className="flex-1 font-medium text-neutral-900">
                                    {item.label}
                                </span>
                                <span className="text-neutral-500">
                                    {formatMoney(item.charge)}
                                </span>
                            </label>
                        ))}
                    </fieldset>
                )}

                {isBuyNow && (
                    <div className="pt-1">
                        <QuantityStepper
                            label="Quantity"
                            value={form.data.quantity}
                            min={MIN_QUANTITY}
                            max={MAX_QUANTITY}
                            onChange={(quantity) => form.setData('quantity', quantity)}
                        />
                    </div>
                )}
            </div>

            <div className="rounded-2xl bg-neutral-100/70 p-4">
                <OrderTotals
                    subtotal={subtotal}
                    deliveryCharge={deliveryCharge}
                />
            </div>

            <Button
                type="submit"
                size="lg"
                fullWidth
                className="rounded-xl py-3.5 font-semibold shadow-lg shadow-accent-600/20 transition-all active:scale-[0.98]"
            >
                <Icon name="bolt" size={18} />
                Confirm Order
            </Button>
            <p className="-mt-2 text-center text-xs font-medium text-neutral-500">
                Payment is Cash on Delivery.
            </p>

            {isPreviewShown && (
                <p
                    role="status"
                    className="animate-fadeIn rounded-2xl border border-accent-100 bg-accent-50 p-4 text-sm font-medium text-accent-700"
                >
                    Your details look good. Online ordering is not live yet, so
                    this order was not placed.
                </p>
            )}
        </form>
    );
}

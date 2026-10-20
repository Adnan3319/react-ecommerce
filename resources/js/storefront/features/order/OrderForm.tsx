import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { formatMoney } from '@/shared/lib';
import { store } from '@/routes/orders';
import { Button, Icon, Input, Textarea } from '@/storefront/components/ui';
import type { DeliveryZone } from '@/storefront/features/product/types';
import DeliveryZonePicker from './DeliveryZonePicker';
import OrderSummary from './OrderSummary';

export const ORDER_FORM_ID = 'order-form';

type OrderFormProps = {
    variantId: number;
    quantity: number;
    /** Minor units, per item; display only. */
    unitPrice: number;
    zones: DeliveryZone[];
    outOfStock: boolean;
};

type OrderFormData = {
    name: string;
    phone: string;
    address: string;
    delivery_zone: string;
    note: string;
    /** Honeypot: bots fill it, people never see it. */
    website: string;
};

/** Order Now: guest details + delivery area for one variant, Cash on Delivery. */
export default function OrderForm({
    variantId,
    quantity,
    unitPrice,
    zones,
    outOfStock,
}: OrderFormProps) {
    const form = useForm<OrderFormData>({
        name: '',
        phone: '',
        address: '',
        delivery_zone: zones[0]?.key ?? '',
        note: '',
        website: '',
    });
    const [noteOpen, setNoteOpen] = useState(false);
    // One key per form: sending the same form twice (double click, retry) can never create two orders.
    const idempotencyKey = useRef<string | null>(null);

    const zone =
        zones.find((candidate) => candidate.key === form.data.delivery_zone) ??
        zones[0];
    const subtotal = unitPrice * quantity;
    const total = subtotal + (zone?.charge ?? 0);
    const errors = form.errors as Partial<Record<string, string>>;
    const lineError =
        errors['items.0.quantity'] ??
        errors['items.0.variant'] ??
        errors.order ??
        errors.items;

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        idempotencyKey.current ??= crypto.randomUUID();
        const key = idempotencyKey.current;

        form.transform((data) => ({
            ...data,
            items: [{ variant_id: variantId, quantity }],
            idempotency_key: key,
        }));
        form.post(store().url, { preserveScroll: true });
    }

    return (
        <form
            id={ORDER_FORM_ID}
            onSubmit={submit}
            noValidate
            aria-label="Order now"
            className="flex scroll-mt-24 flex-col gap-4 rounded-[28px] bg-white p-6 shadow-[0_8px_30px_rgba(15,23,42,0.06)]"
        >
            <div>
                <h2 className="text-xl font-bold tracking-tight text-neutral-900">
                    Order now · Cash on Delivery
                </h2>
                <p className="mt-1 text-sm text-neutral-500">
                    No account needed. Pay when it arrives.
                </p>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <Input
                    label="Your name"
                    name="name"
                    autoComplete="name"
                    value={form.data.name}
                    error={errors.name}
                    onChange={(event) =>
                        form.setData('name', event.target.value)
                    }
                />
                <Input
                    label="Phone number"
                    name="phone"
                    type="tel"
                    inputMode="numeric"
                    autoComplete="tel"
                    placeholder="01XXXXXXXXX"
                    value={form.data.phone}
                    error={errors.phone}
                    onChange={(event) =>
                        form.setData('phone', event.target.value)
                    }
                />
            </div>

            <Textarea
                label="Full address"
                name="address"
                autoComplete="street-address"
                placeholder="House, road, area, thana, district"
                value={form.data.address}
                error={errors.address}
                onChange={(event) =>
                    form.setData('address', event.target.value)
                }
            />

            <DeliveryZonePicker
                zones={zones}
                value={form.data.delivery_zone}
                error={errors.delivery_zone}
                onChange={(key) => form.setData('delivery_zone', key)}
            />

            {noteOpen ? (
                <Textarea
                    label="Note (optional)"
                    name="note"
                    value={form.data.note}
                    error={errors.note}
                    onChange={(event) =>
                        form.setData('note', event.target.value)
                    }
                />
            ) : (
                <button
                    type="button"
                    onClick={() => setNoteOpen(true)}
                    className="w-fit text-sm font-medium text-accent-600 hover:text-accent-700"
                >
                    + Add a note (optional)
                </button>
            )}

            <div
                aria-hidden="true"
                className="absolute -start-[9999px] h-0 w-0 overflow-hidden"
            >
                <label>
                    Website
                    <input
                        type="text"
                        name="website"
                        tabIndex={-1}
                        autoComplete="off"
                        value={form.data.website}
                        onChange={(event) =>
                            form.setData('website', event.target.value)
                        }
                    />
                </label>
            </div>

            <OrderSummary
                subtotal={subtotal}
                deliveryCharge={zone?.charge ?? 0}
                itemCount={quantity}
            />

            {lineError && (
                <p
                    role="alert"
                    className="rounded-control bg-red-50 px-3 py-2 text-sm text-red-700"
                >
                    {lineError}
                </p>
            )}

            <Button
                type="submit"
                size="lg"
                fullWidth
                disabled={outOfStock || form.processing}
                loading={form.processing}
            >
                {outOfStock ? (
                    'Out of stock'
                ) : (
                    <>
                        <Icon name="bolt" size={18} />
                        Confirm Order · {formatMoney(total)}
                    </>
                )}
            </Button>
            <p className="-mt-1 flex items-center justify-center gap-4 text-xs text-neutral-500">
                <span className="inline-flex items-center gap-1">
                    <Icon name="check" size={14} /> Cash on delivery
                </span>
                <span className="inline-flex items-center gap-1">
                    <Icon name="check" size={14} /> We confirm by phone
                </span>
            </p>
        </form>
    );
}

import { isBangladeshiMobile } from '@/shared/lib';

export type OrderSource = 'buy_now' | 'cart';

/** Payload sent to the order endpoint; `productId` is set only for `buy_now`. */
export type OrderValues = {
    source: OrderSource;
    productId: number | null;
    name: string;
    phone: string;
    address: string;
    quantity: number;
};

export type OrderErrors = Partial<Record<keyof OrderValues, string>>;

export const MIN_QUANTITY = 1;
export const MAX_QUANTITY = 10;

const MIN_NAME_LENGTH = 2;
const MIN_ADDRESS_LENGTH = 10;

/** Client-side validation is UX only; the backend remains the source of truth. */
export function validateOrder(values: OrderValues): OrderErrors {
    const errors: OrderErrors = {};

    if (values.name.trim().length < MIN_NAME_LENGTH) {
        errors.name = 'Please enter your name.';
    }

    if (!isBangladeshiMobile(values.phone)) {
        errors.phone = 'Enter a valid mobile number, e.g. 01XXXXXXXXX.';
    }

    if (values.address.trim().length < MIN_ADDRESS_LENGTH) {
        errors.address = 'Please enter your full delivery address.';
    }

    const isQuantityInvalid =
        !Number.isInteger(values.quantity) ||
        values.quantity < MIN_QUANTITY ||
        values.quantity > MAX_QUANTITY;

    if (values.source === 'buy_now' && isQuantityInvalid) {
        errors.quantity = `Quantity must be between ${MIN_QUANTITY} and ${MAX_QUANTITY}.`;
    }

    return errors;
}

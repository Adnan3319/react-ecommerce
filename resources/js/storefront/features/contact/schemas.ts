import { isBangladeshiMobile } from '@/shared/lib';

export type ContactValues = {
    name: string;
    phone: string;
    message: string;
};

export type ContactErrors = Partial<Record<keyof ContactValues, string>>;

const MIN_NAME_LENGTH = 2;
const MIN_MESSAGE_LENGTH = 10;

/** Client-side validation is UX only; the backend remains the source of truth. */
export function validateContact(values: ContactValues): ContactErrors {
    const errors: ContactErrors = {};

    if (values.name.trim().length < MIN_NAME_LENGTH) {
        errors.name = 'Please enter your name.';
    }

    if (!isBangladeshiMobile(values.phone)) {
        errors.phone = 'Enter a valid mobile number, e.g. 01XXXXXXXXX.';
    }

    if (values.message.trim().length < MIN_MESSAGE_LENGTH) {
        errors.message = 'Please write a short message.';
    }

    return errors;
}

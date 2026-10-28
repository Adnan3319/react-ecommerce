import { isBangladeshiMobile } from '@/shared/lib';

export type ContactValues = {
    name: string;
    phone: string;
    email: string;
    message: string;
    /** Honeypot: always empty for real visitors. */
    website: string;
};

export type ContactErrors = Partial<Record<keyof ContactValues, string>>;

/** Mirrors `App\Models\ContactMessage::MAX_MESSAGE_LENGTH`. */
export const MAX_MESSAGE_LENGTH = 1000;

const MIN_NAME_LENGTH = 2;
const MIN_MESSAGE_LENGTH = 10;
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/** Client-side validation is UX only; the backend remains the source of truth. */
export function validateContact(values: ContactValues): ContactErrors {
    const errors: ContactErrors = {};

    if (values.name.trim().length < MIN_NAME_LENGTH) {
        errors.name = 'Please enter your name.';
    }

    if (!isBangladeshiMobile(values.phone)) {
        errors.phone = 'Enter a valid mobile number, e.g. 01XXXXXXXXX.';
    }

    if (
        values.email.trim() !== '' &&
        !EMAIL_PATTERN.test(values.email.trim())
    ) {
        errors.email = 'Enter a valid email address.';
    }

    if (values.message.trim().length < MIN_MESSAGE_LENGTH) {
        errors.message = 'Please write a short message.';
    }

    return errors;
}

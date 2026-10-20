export type Category = {
    slug: string;
    label: string;
    /** Optional tagline; when set, the name becomes a small label above it. */
    headline: string | null;
    /** Optional short line shown under the name. */
    description: string | null;
    /** Category photo URL from the backend; null falls back to an illustration. */
    image: string | null;
};

/** A top-level category with its active sub-categories (shape of the shared `categories` prop). */
export type CategoryNode = Category & { children: Category[] };

export const LOGO = {
    src: '/images/logo/checkit-logo.png',
    lightSrc: '/images/logo/checkit-logo-light.png',
    width: 246,
    height: 182,
} as const;

// Dummy contact details for the design preview; replace with real ones.
export const CONTACT_INFO = {
    phone: '+880 1XXX-XXXXXX',
    email: 'hello@checkit.example',
    address: 'Dhaka, Bangladesh',
    hours: 'Saturday to Thursday, 10:00 AM to 8:00 PM',
} as const;

// Placeholder (minor units) until the delivery charge is configured in admin.
export const DEFAULT_DELIVERY_CHARGE = 6000;

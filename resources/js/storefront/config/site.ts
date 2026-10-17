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

// Static until the categories table exists.
export const CATEGORIES: Category[] = [
    {
        slug: 'kitchenware',
        label: 'Kitchenware',
        headline: 'Cook with Joy',
        description: 'Premium kitchen essentials for your home.',
        image: '/images/categories/kitchenware.jpg',
    },
    {
        slug: 'fashion',
        label: 'Fashion',
        headline: 'Dress Your Style',
        description: 'Trendy and comfortable for every occasion.',
        image: '/images/categories/fashion.jpg',
    },
];

export function findCategory(slug: string): Category | undefined {
    return CATEGORIES.find((category) => category.slug === slug);
}

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

export type Category = {
    slug: string;
    label: string;
};

// Static until the categories table exists.
export const CATEGORIES: Category[] = [
    { slug: 'kitchenware', label: 'Kitchenware' },
    { slug: 'fashion', label: 'Fashion' },
];

export function findCategory(slug: string): Category | undefined {
    return CATEGORIES.find((category) => category.slug === slug);
}

export const LOGO = {
    src: '/images/logo/checkit-logo.png',
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

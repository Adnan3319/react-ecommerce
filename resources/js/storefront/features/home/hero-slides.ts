/** Shape the backend sends for each hero slide (managed in admin). */
export type HeroSlide = {
    id: number;
    title: string;
    /** Optional second title line, rendered in the accent color. */
    highlight: string | null;
    subtitle: string;
    image: string;
    imageAlt: string;
    imageWidth: number;
    imageHeight: number;
    /** The button renders only when this is set. */
    cta: {
        label: string;
        href: string;
    } | null;
};

// Sample content for the static design phase.
// Photos live in public/images/sliders/{n}.jpg.
export const HERO_SLIDES: HeroSlide[] = [
    {
        id: 1,
        title: 'Smart Kitchen',
        highlight: 'Essentials',
        subtitle:
            'From electric kettles to fruit presses, everyday tools that make cooking easier.',
        image: '/images/sliders/1.jpg',
        imageAlt: 'Electric glass kettle boiling water in a kitchen',
        imageWidth: 750,
        imageHeight: 1000,
        cta: { label: 'Shop Now', href: '/search' },
    },
    {
        id: 2,
        title: 'Store Smart,',
        highlight: 'Keep It Neat',
        subtitle:
            'Rotating grain dispensers and egg storage that keep your counter tidy.',
        image: '/images/sliders/2.jpg',
        imageAlt: 'Rotating grain dispenser with transparent compartments',
        imageWidth: 1000,
        imageHeight: 1000,
        cta: { label: 'Shop Now', href: '/search' },
    },
    {
        id: 3,
        title: 'Glass Holders',
        highlight: 'That Shine',
        subtitle:
            'Gold-finish stands that dry and display your glasses beautifully.',
        image: '/images/sliders/3.jpg',
        imageAlt: 'Gold glass holder stand with a crystal ball top',
        imageWidth: 1000,
        imageHeight: 1000,
        cta: null,
    },
];

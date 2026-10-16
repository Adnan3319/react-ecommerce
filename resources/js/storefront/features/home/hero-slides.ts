export type HeroSlide = {
    id: number;
    eyebrow: string;
    title: string;
    description: string;
    image: string;
    imageAlt: string;
    imageWidth: number;
    imageHeight: number;
};

// Sample content for the static design phase.
// Photos live in public/images/sliders/{n}.jpg.
export const HERO_SLIDES: HeroSlide[] = [
    {
        id: 1,
        eyebrow: 'Kitchenware',
        title: 'Smart Kitchen Essentials',
        description:
            'From electric kettles to fruit presses, everyday tools that make cooking easier.',
        image: '/images/sliders/1.jpg',
        imageAlt: 'Electric glass kettle boiling water in a kitchen',
        imageWidth: 750,
        imageHeight: 1000,
    },
    {
        id: 2,
        eyebrow: 'Stay organized',
        title: 'Store Smart, Keep It Neat',
        description:
            'Rotating grain dispensers and egg storage that keep your counter tidy.',
        image: '/images/sliders/2.jpg',
        imageAlt: 'Rotating grain dispenser with transparent compartments',
        imageWidth: 1000,
        imageHeight: 1000,
    },
    {
        id: 3,
        eyebrow: 'Elegant finish',
        title: 'Glass Holders That Shine',
        description:
            'Gold-finish stands that dry and display your glasses beautifully.',
        image: '/images/sliders/3.jpg',
        imageAlt: 'Gold glass holder stand with a crystal ball top',
        imageWidth: 1000,
        imageHeight: 1000,
    },
];

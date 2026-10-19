/** Shape the hero slider renders for each slide. */
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

/** Shape the backend sends for each slider (managed in admin). */
export type ApiSlider = {
    id: number;
    title: string;
    /** Optional second title line, shown in the accent color. */
    highlight?: string | null;
    subtitle: string | null;
    image: string;
    button_text: string | null;
    button_link: string | null;
};

/** Backend slider -> hero slide. The button shows only when both text and link are set. */
export function toHeroSlide(slider: ApiSlider): HeroSlide {
    return {
        id: slider.id,
        title: slider.title,
        highlight: slider.highlight ?? null,
        subtitle: slider.subtitle ?? '',
        image: slider.image,
        imageAlt: slider.title,
        imageWidth: 1000,
        imageHeight: 1000,
        cta: slider.button_link
            ? { label: slider.button_text || 'Shop Now', href: slider.button_link }
            : null,
    };
}

// Sample content, used only while the database has no active sliders.
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
import { Link } from '@inertiajs/react';
import { cn } from '@/shared/lib';
import { Icon, Image } from '@/storefront/components/ui';
import type { RouteDefinition } from '@/wayfinder';
import { CategoryIcon } from '../CategoryIcon';

type CategoryCardProps = {
    slug: string;
    label: string;
    headline: string | null;
    description: string | null;
    /** Photo from the backend; null shows the category illustration. */
    image: string | null;
    href: RouteDefinition<'get'>;
};

// Pastel tile backgrounds sampled from the design; one place to recolor.
const SURFACES: Record<string, string> = {
    kitchenware: 'from-[#fdf9f6] via-[#f6e4d5] to-[#f3d4bd]',
    fashion: 'from-[#fff0f0] via-[#f9d3d5] to-[#f2bebf]',
};

const DEFAULT_SURFACE = 'from-neutral-50 to-neutral-200';
const IMAGE_SIZE = 480;
const ICON_SIZE = 160;

/** Wide pastel tile: category image on the left, text on the right. */
export default function CategoryCard({
    slug,
    label,
    headline,
    description,
    image,
    href,
}: CategoryCardProps) {
    return (
        <Link
            href={href}
            className={cn(
                'group relative flex min-h-48 items-center overflow-hidden rounded-card bg-linear-to-br p-6 shadow-card transition-all duration-300 sm:min-h-56 sm:p-8',
                'hover:-translate-y-1 hover:shadow-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600',
                SURFACES[slug] ?? DEFAULT_SURFACE,
            )}
        >
            <div className="absolute inset-y-0 left-0 flex w-1/2 items-center justify-center">
                {image ? (
                    <Image
                        src={image}
                        alt=""
                        width={IMAGE_SIZE}
                        height={IMAGE_SIZE}
                        className="h-full w-full object-contain p-3 mix-blend-multiply transition-transform duration-500 group-hover:scale-105"
                    />
                ) : (
                    <CategoryIcon
                        slug={slug}
                        size={ICON_SIZE}
                        className="h-auto w-32 drop-shadow-lg transition-transform duration-500 group-hover:scale-110 sm:w-44"
                    />
                )}
            </div>

            <div className="relative ml-auto flex w-[46%] flex-col items-start gap-2">
                {headline && (
                    <span className="text-[0.7rem] font-semibold tracking-[0.2em] text-neutral-600 uppercase">
                        {label}
                    </span>
                )}
                <h3 className="text-2xl leading-tight font-extrabold tracking-tight text-neutral-900 sm:text-3xl">
                    {headline ?? label}
                </h3>
                {description && (
                    <p className="text-sm text-neutral-700">{description}</p>
                )}
                <span
                    aria-hidden="true"
                    className="mt-3 flex size-9 items-center justify-center rounded-full btn-accent group-hover:translate-x-1"
                >
                    <Icon name="arrowRight" size={16} />
                </span>
            </div>
        </Link>
    );
}

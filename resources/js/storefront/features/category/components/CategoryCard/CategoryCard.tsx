import { Link } from '@inertiajs/react';
import { cn } from '@/shared/lib';
import type { RouteDefinition } from '@/wayfinder';
import { CategoryIcon } from '../CategoryIcon';

type CategoryCardProps = {
    slug: string;
    label: string;
    href: RouteDefinition<'get'>;
};

// Category illustration palette; one place to recolor the tiles.
const SURFACES: Record<string, string> = {
    kitchenware: 'bg-orange-500',
    fashion: 'bg-gradient-to-br from-pink-500 to-pink-600',
};

const DEFAULT_SURFACE = 'bg-neutral-800';
const ICON_SIZE = 96;

/** Colored category tile with a white icon and a bold centered label. */
export default function CategoryCard({ slug, label, href }: CategoryCardProps) {
    return (
        <Link
            href={href}
            className={cn(
                'group relative flex aspect-6/5 flex-col items-center justify-between overflow-hidden rounded-card px-4 pt-5 pb-4 text-white shadow-card transition-all duration-300',
                'hover:-translate-y-1 hover:shadow-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600',
                SURFACES[slug] ?? DEFAULT_SURFACE,
            )}
        >
            <span
                aria-hidden="true"
                className="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-white/15 transition-transform duration-500 group-hover:scale-125 sm:h-40 sm:w-40"
            />
            <span
                aria-hidden="true"
                className="absolute -bottom-8 -left-8 h-20 w-20 rounded-full bg-white/30 transition-transform duration-500 group-hover:scale-125 sm:h-24 sm:w-24"
            />

            <span className="relative flex flex-1 items-center justify-center transition-transform duration-300 group-hover:scale-110">
                <CategoryIcon
                    slug={slug}
                    size={ICON_SIZE}
                    className="h-auto w-16 drop-shadow-md sm:w-24"
                />
            </span>

            <span className="relative text-base font-extrabold tracking-tight sm:text-xl">
                {label}
            </span>
        </Link>
    );
}

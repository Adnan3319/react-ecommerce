import { Link } from '@inertiajs/react';
import { cn } from '@/shared/lib';
import { Icon, Image, SectionHeader } from '@/storefront/components/ui';
import { categoryHref } from '@/storefront/lib/category-href';
import type { HomeCategory } from './types';

type CategoryShowcaseProps = {
    categories: HomeCategory[];
};

const IMAGE_SIZE = 300;

// Cards alternate between the brand accent and the dark ink color.
const TONES = ['bg-accent-600', 'bg-ink'] as const;

export default function CategoryShowcase({
    categories,
}: CategoryShowcaseProps) {
    if (categories.length === 0) {
        return null;
    }

    return (
        <section className="mx-auto max-w-7xl px-4 pt-12 sm:px-6">
            <SectionHeader title="Shop by Category" accent />
            <ul className="grid gap-4 sm:grid-cols-2 sm:gap-6">
                {categories.map((category, index) => (
                    <li key={category.slug}>
                        <article
                            className={cn(
                                'group relative flex min-h-48 items-end justify-between gap-4 overflow-hidden rounded-card p-6 text-white shadow-card sm:min-h-56 sm:p-8',
                                TONES[index % TONES.length],
                            )}
                        >
                            {category.image && (
                                <div className="absolute top-4 right-4 flex size-28 items-center justify-center rounded-full bg-white/90 p-3 sm:size-40">
                                    <Image
                                        src={category.image}
                                        alt=""
                                        width={IMAGE_SIZE}
                                        height={IMAGE_SIZE}
                                        className="size-full object-contain transition-transform duration-500 group-hover:scale-105"
                                    />
                                </div>
                            )}
                            <div className="relative flex flex-col items-start gap-3">
                                <h3 className="text-2xl font-extrabold tracking-tight sm:text-3xl">
                                    <Link
                                        href={categoryHref(category.slug)}
                                        className="after:absolute after:inset-0 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white"
                                    >
                                        {category.label}
                                    </Link>
                                </h3>
                                {category.children.length > 0 && (
                                    <ul className="relative z-10 flex flex-wrap gap-2">
                                        {category.children.map((child) => (
                                            <li key={child.slug}>
                                                <Link
                                                    href={categoryHref(
                                                        child.slug,
                                                    )}
                                                    className="inline-flex h-8 items-center rounded-full bg-white/15 px-3 text-xs font-medium ring-1 ring-white/30 transition-colors hover:bg-white/25"
                                                >
                                                    {child.label}
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                            <Icon
                                name="arrowRight"
                                size={20}
                                className="relative hidden shrink-0 transition-transform group-hover:translate-x-1 sm:block"
                            />
                        </article>
                    </li>
                ))}
            </ul>
        </section>
    );
}

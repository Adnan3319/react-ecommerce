import { Link } from '@inertiajs/react';
import { search } from '@/routes';
import { show } from '@/routes/categories';
import { cn } from '@/shared/lib';
import type { CategoryNode } from '@/storefront/config/site';
import { useCategories } from '@/storefront/lib/useCategories';
import type { ListingCategory, ListingMode } from './types';

type SubCategoryTabsProps = {
    mode: ListingMode;
    category: ListingCategory | null;
    q: string | null;
};

type Tab = { key: string; label: string; href: string; active: boolean };

const TAB =
    'inline-flex h-10 items-center rounded-full border px-5 text-sm font-medium whitespace-nowrap transition-colors';

function rootOf(
    categories: CategoryNode[],
    category: ListingCategory | null,
): CategoryNode | undefined {
    const rootSlug = category?.parent?.slug ?? category?.slug;

    return categories.find((root) => root.slug === rootSlug);
}

/**
 * Category pages show "All" plus the sub-categories of the root; the search page shows "All" plus
 * the root categories. The search term is kept when switching.
 */
function buildTabs(
    mode: ListingMode,
    categories: CategoryNode[],
    category: ListingCategory | null,
    q: string | null,
): Tab[] {
    const root = rootOf(categories, category);

    if (mode === 'category') {
        if (!root || root.children.length === 0) {
            return [];
        }

        return [
            {
                key: root.slug,
                label: 'All',
                href: show(root.slug).url,
                active: category?.slug === root.slug,
            },
            ...root.children.map((child) => ({
                key: child.slug,
                label: child.label,
                href: show(child.slug).url,
                active: category?.slug === child.slug,
            })),
        ];
    }

    const withTerm = (slug: string | null) =>
        search({
            query: { ...(q ? { q } : {}), ...(slug ? { category: slug } : {}) },
        }).url;

    return [
        {
            key: 'all',
            label: 'All',
            href: withTerm(null),
            active: category === null,
        },
        ...categories.map((item) => ({
            key: item.slug,
            label: item.label,
            href: withTerm(item.slug),
            active: root?.slug === item.slug,
        })),
    ];
}

export default function SubCategoryTabs({
    mode,
    category,
    q,
}: SubCategoryTabsProps) {
    const tabs = buildTabs(mode, useCategories(), category, q);

    if (tabs.length === 0) {
        return null;
    }

    return (
        <nav aria-label="Categories">
            <ul className="flex gap-2 overflow-x-auto pb-1">
                {tabs.map((tab) => (
                    <li key={tab.key} className="shrink-0">
                        <Link
                            href={tab.href}
                            aria-current={tab.active ? 'page' : undefined}
                            className={cn(
                                TAB,
                                tab.active
                                    ? 'border-neutral-900 bg-neutral-900 text-white'
                                    : 'border-neutral-300 bg-white text-neutral-800 hover:border-neutral-500',
                            )}
                        >
                            {tab.label}
                        </Link>
                    </li>
                ))}
            </ul>
        </nav>
    );
}

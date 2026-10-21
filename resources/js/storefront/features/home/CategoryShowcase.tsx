import { categoryHref } from '@/storefront/lib/category-href';
import CategoryCard from './CategoryCard';
import type { HomeCategory } from './types';

type CategoryShowcaseProps = {
    categories: HomeCategory[];
};

/** Top-level categories only; sub-categories are reached from the category page. */
export default function CategoryShowcase({
    categories,
}: CategoryShowcaseProps) {
    if (categories.length === 0) {
        return null;
    }

    return (
        <section
            aria-label="Shop by category"
            className="mx-auto max-w-7xl px-4 pt-6 sm:px-6"
        >
            <ul className="grid gap-4 sm:grid-cols-2 sm:gap-6">
                {categories.map((category) => (
                    <li key={category.slug}>
                        <CategoryCard
                            slug={category.slug}
                            label={category.label}
                            description={category.description}
                            image={category.image}
                            href={categoryHref(category.slug)}
                        />
                    </li>
                ))}
            </ul>
        </section>
    );
}

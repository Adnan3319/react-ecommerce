import { CategoryCard } from '@/storefront/features/category';
import { CATEGORIES } from '@/storefront/config/site';
import { categoryHref } from '@/storefront/lib/category-href';

export default function CategoryShowcase() {
    return (
        <section className="mx-auto max-w-7xl px-4 pt-12 sm:px-6">
            <div className="mb-8 text-center">
                <h2 className="text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">
                    Shop by Category
                </h2>
                <p className="mt-2 text-sm text-neutral-600">
                    Pick a category and find what you need.
                </p>
            </div>
            <ul className="mx-auto grid max-w-xl grid-cols-2 gap-4 sm:gap-6">
                {CATEGORIES.map((category) => (
                    <li key={category.slug}>
                        <CategoryCard
                            slug={category.slug}
                            label={category.label}
                            href={categoryHref(category.slug)}
                        />
                    </li>
                ))}
            </ul>
        </section>
    );
}

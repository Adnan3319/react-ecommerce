import { SectionHeader } from '@/shared/ui';
import { CATEGORIES } from '@/storefront/config/site';
import { CategoryCard } from '@/storefront/features/category';
import { categoryHref } from '@/storefront/lib/category-href';

export default function CategoryShowcase() {
    return (
        <section className="mx-auto max-w-7xl px-4 pt-12 sm:px-6">
            <SectionHeader
                title="Shop by Category"
                subtitle="Pick a category and find what you need."
                accent
            />
            <ul className="grid gap-4 sm:grid-cols-2 sm:gap-6">
                {CATEGORIES.map((category) => (
                    <li key={category.slug}>
                        <CategoryCard
                            slug={category.slug}
                            label={category.label}
                            headline={category.headline}
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

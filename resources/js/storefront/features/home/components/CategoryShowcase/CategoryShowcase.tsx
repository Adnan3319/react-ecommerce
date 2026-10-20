import { SectionHeader } from '@/storefront/components/ui';
import { CategoryCard } from '@/storefront/features/category';
import { categoryHref } from '@/storefront/lib/category-href';
import { useCategories } from '@/storefront/lib/useCategories';

export default function CategoryShowcase() {
    const categories = useCategories();

    if (categories.length === 0) {
        return null;
    }

    return (
        <section className="mx-auto max-w-7xl px-4 pt-12 sm:px-6">
            <SectionHeader
                title="Shop by Category"
                subtitle="Pick a category and find what you need."
                accent
            />
            <ul className="grid gap-4 sm:grid-cols-2 sm:gap-6">
                {categories.map((category) => (
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

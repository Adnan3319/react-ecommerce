import { Link } from '@inertiajs/react';
import { Button } from '@/storefront/components/ui';
import { categoryHref } from '@/storefront/lib/category-href';
import { useCategories } from '@/storefront/lib/useCategories';

type EmptyResultsProps = {
    q: string | null;
    narrowed: boolean;
    onClear: () => void;
};

export default function EmptyResults({
    q,
    narrowed,
    onClear,
}: EmptyResultsProps) {
    const categories = useCategories();

    return (
        <div className="rounded-card bg-white px-6 py-14 text-center shadow-card ring-1 ring-neutral-200">
            <p className="text-lg font-semibold text-neutral-900">
                No products found
            </p>
            <p className="mt-2 text-sm text-neutral-600">
                {q ? `Nothing found for “${q}”. ` : ''}
                {narrowed
                    ? 'Try removing a filter.'
                    : 'Try another word, or browse '}
                {!narrowed &&
                    categories.map((category, index) => (
                        <span key={category.slug}>
                            {index > 0 && ' · '}
                            <Link
                                href={categoryHref(category.slug)}
                                className="font-semibold text-accent-600 hover:text-accent-700"
                            >
                                {category.label}
                            </Link>
                        </span>
                    ))}
            </p>
            {narrowed && (
                <Button className="mt-5" variant="outline" onClick={onClear}>
                    Clear filters
                </Button>
            )}
        </div>
    );
}

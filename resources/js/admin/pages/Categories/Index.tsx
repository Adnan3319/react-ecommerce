import { Head } from '@inertiajs/react';
import CategoryManager from '@admin/features/categories/CategoryManager';
import type { CategoryIndexProps } from '@admin/features/categories/types';

export default function CategoryIndex(props: CategoryIndexProps) {
    return (
        <>
            <Head title="Categories" />
            <CategoryManager {...props} />
        </>
    );
}

import { Head } from '@inertiajs/react';
import ProductManager from '@admin/features/products/ProductManager';
import type { ProductIndexProps } from '@admin/features/products/types';

export default function ProductIndex(props: ProductIndexProps) {
    return (
        <>
            <Head title="Products" />
            <ProductManager {...props} />
        </>
    );
}

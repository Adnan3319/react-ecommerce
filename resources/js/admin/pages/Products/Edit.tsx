import { Head } from '@inertiajs/react';
import ProductForm from '@admin/features/products/ProductForm';
import type { ProductFormProps } from '@admin/features/products/types';

export default function ProductEdit(props: ProductFormProps) {
    return (
        <>
            <Head title="Edit product" />
            <ProductForm {...props} />
        </>
    );
}

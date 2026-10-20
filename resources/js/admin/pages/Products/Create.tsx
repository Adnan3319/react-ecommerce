import { Head } from '@inertiajs/react';
import ProductForm from '@admin/features/products/ProductForm';
import type { ProductFormProps } from '@admin/features/products/types';

export default function ProductCreate(props: ProductFormProps) {
    return (
        <>
            <Head title="Add product" />
            <ProductForm {...props} />
        </>
    );
}

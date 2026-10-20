import { useForm } from '@inertiajs/react';
import type { TabId } from './constants';
import { initialFormState, tabsWithErrors, toPayload } from './form-state';
import type {
    FormApi,
    FormErrors,
    ProductDetail,
    ProductFormData,
} from './types';
import { store, update as updateRoute } from '@/routes/admin/products';

type Callbacks = {
    onErrorTabs: (tabs: TabId[]) => void;
    onSaved: () => void;
};

/**
 * The product form state plus submit: multipart (`forceFormData`), `_method=put` on edit, and the tabs that
 * contain server errors reported back so the shell can jump to the first one.
 */
export function useProductForm(
    product: ProductDetail | undefined,
    { onErrorTabs, onSaved }: Callbacks,
) {
    const isEdit = product !== undefined;
    const form = useForm<ProductFormData>(initialFormState(product));

    const api: FormApi = {
        data: form.data,
        errors: form.errors as FormErrors,
        set: (key, value) =>
            form.setData((previous) => ({ ...previous, [key]: value })),
        update: (updater) => form.setData(updater),
    };

    const submit = () => {
        form.transform((data) => toPayload(data, isEdit));
        form.post(isEdit ? updateRoute(product.id).url : store().url, {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors) => onErrorTabs(tabsWithErrors(errors)),
            onSuccess: () => {
                if (isEdit) {
                    onSaved();
                }
            },
        });
    };

    return { api, submit, processing: form.processing, isEdit };
}

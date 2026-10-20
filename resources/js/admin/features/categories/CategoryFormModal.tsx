import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import Checkbox from '@admin/components/form/input/Checkbox';
import FileInput from '@admin/components/form/input/FileInput';
import Input from '@admin/components/form/input/InputField';
import TextArea from '@admin/components/form/input/TextArea';
import Label from '@admin/components/form/Label';
import Select from '@admin/components/form/Select';
import Button from '@admin/components/ui/button/Button';
import { Modal } from '@admin/components/ui/modal';
import { store, update } from '@/routes/admin/categories';
import { STATUS_ACTIVE } from '@/shared/constants/status';
import type { ActiveStatus, StatusOption } from '@/shared/types';
import type { Category } from './types';

type CategoryFormModalProps = {
    category: Category | null;
    statuses: StatusOption[];
    defaultSortOrder: number;
    onClose: () => void;
};

type CategoryFormData = {
    name: string;
    slug: string;
    headline: string;
    description: string;
    sort_order: string;
    status: ActiveStatus;
    image: File | null;
    remove_image: boolean;
};

const noop = () => undefined;

/**
 * Mounted only while open, so every open starts from fresh form state.
 */
export default function CategoryFormModal({
    category,
    statuses,
    defaultSortOrder,
    onClose,
}: CategoryFormModalProps) {
    const isEdit = category !== null;

    const form = useForm<CategoryFormData>({
        name: category?.name ?? '',
        slug: category?.slug ?? '',
        headline: category?.headline ?? '',
        description: category?.description ?? '',
        sort_order: String(category?.sort_order ?? defaultSortOrder),
        status: category?.status ?? STATUS_ACTIVE,
        image: null,
        remove_image: false,
    });

    const [preview, setPreview] = useState<string | null>(null);

    useEffect(
        () => () => {
            if (preview) {
                URL.revokeObjectURL(preview);
            }
        },
        [preview],
    );

    const currentImage = preview ?? (form.data.remove_image ? null : (category?.image_url ?? null));

    const handleImageChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;

        form.setData((data) => ({ ...data, image: file, remove_image: false }));
        setPreview(file ? URL.createObjectURL(file) : null);
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const options = { forceFormData: true, preserveScroll: true, onSuccess: onClose };

        if (isEdit) {
            // Multipart bodies need POST; Laravel reads the spoofed method.
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(update(category.id).url, options);

            return;
        }

        form.post(store().url, options);
    };

    return (
        <Modal
            isOpen
            onClose={form.processing ? noop : onClose}
            className="m-4 max-h-[90vh] max-w-2xl overflow-y-auto p-6 sm:p-8"
        >
            <form onSubmit={submit} noValidate className="space-y-4">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">
                    {isEdit ? 'Edit category' : 'Add category'}
                </h2>

                <div>
                    <Label>Image (optional)</Label>
                    <div className="flex items-center gap-4">
                        <div className="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100 text-xs text-gray-400 dark:bg-white/5">
                            {currentImage ? (
                                <img
                                    src={currentImage}
                                    alt=""
                                    width={80}
                                    height={80}
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                'No image'
                            )}
                        </div>
                        <div className="min-w-0 flex-1 space-y-2">
                            <FileInput
                                accept="image/jpeg,image/png,image/webp"
                                aria-label="Category image"
                                onChange={handleImageChange}
                            />
                            <p className="text-xs text-gray-500">
                                Square logo, 300–1000px, max 512KB
                            </p>
                            {isEdit && category.image_url && !form.data.image && (
                                <Checkbox
                                    label="Remove current image"
                                    checked={form.data.remove_image}
                                    onChange={(checked) => form.setData('remove_image', checked)}
                                />
                            )}
                        </div>
                    </div>
                    {form.errors.image && (
                        <p className="mt-1.5 text-xs text-error-500">{form.errors.image}</p>
                    )}
                </div>

                <div>
                    <Label htmlFor="category-name">Name</Label>
                    <Input
                        id="category-name"
                        value={form.data.name}
                        onChange={(event) => form.setData('name', event.target.value)}
                        placeholder="Kitchenware"
                        error={Boolean(form.errors.name)}
                        hint={form.errors.name}
                    />
                </div>

                <div>
                    <Label htmlFor="category-slug">Slug (URL name)</Label>
                    <Input
                        id="category-slug"
                        value={form.data.slug}
                        onChange={(event) => form.setData('slug', event.target.value)}
                        placeholder="kitchenware"
                        error={Boolean(form.errors.slug)}
                        hint={
                            form.errors.slug ??
                            'Leave empty to create it from the name. Letters, numbers, dashes and underscores only.'
                        }
                    />
                </div>

                <div>
                    <Label htmlFor="category-headline">Headline (optional)</Label>
                    <Input
                        id="category-headline"
                        value={form.data.headline}
                        onChange={(event) => form.setData('headline', event.target.value)}
                        placeholder="Cook with Joy"
                        error={Boolean(form.errors.headline)}
                        hint={form.errors.headline}
                    />
                </div>

                <div>
                    <Label>Description (optional)</Label>
                    <TextArea
                        rows={2}
                        value={form.data.description}
                        onChange={(value) => form.setData('description', value)}
                        placeholder="Premium kitchen essentials for your home."
                        error={Boolean(form.errors.description)}
                        hint={form.errors.description}
                    />
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <Label htmlFor="category-order">Sort order</Label>
                        <Input
                            id="category-order"
                            type="number"
                            min={0}
                            value={form.data.sort_order}
                            onChange={(event) => form.setData('sort_order', event.target.value)}
                            error={Boolean(form.errors.sort_order)}
                            hint={form.errors.sort_order}
                        />
                    </div>
                    <div>
                        <Label>Status</Label>
                        <Select
                            options={statuses}
                            defaultValue={form.data.status}
                            placeholder="Select status"
                            onChange={(value) => form.setData('status', value as ActiveStatus)}
                        />
                        {form.errors.status && (
                            <p className="mt-1.5 text-xs text-error-500">{form.errors.status}</p>
                        )}
                    </div>
                </div>

                <div className="flex justify-end gap-3 pt-2">
                    <Button variant="outline" size="sm" onClick={onClose} disabled={form.processing}>
                        Cancel
                    </Button>
                    <Button type="submit" size="sm" disabled={form.processing}>
                        {form.processing ? 'Saving...' : isEdit ? 'Save changes' : 'Create category'}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}

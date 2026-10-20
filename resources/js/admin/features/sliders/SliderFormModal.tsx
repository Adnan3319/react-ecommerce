import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import FileInput from '@admin/components/form/input/FileInput';
import Input from '@admin/components/form/input/InputField';
import Label from '@admin/components/form/Label';
import Select from '@admin/components/form/Select';
import Button from '@admin/components/ui/button/Button';
import { Modal } from '@admin/components/ui/modal';
import { store, update } from '@/routes/admin/sliders';
import { STATUS_ACTIVE } from '@/shared/constants/status';
import type { ActiveStatus, StatusOption } from '@/shared/types';
import type { Slider } from './types';

type SliderFormModalProps = {
    slider: Slider | null;
    statuses: StatusOption[];
    defaultSortOrder: number;
    onClose: () => void;
};

type SliderFormData = {
    title: string;
    subtitle: string;
    button_text: string;
    button_link: string;
    sort_order: string;
    status: ActiveStatus;
    image: File | null;
};

const noop = () => undefined;

/**
 * Mounted only while open, so every open starts from fresh form state.
 */
export default function SliderFormModal({
    slider,
    statuses,
    defaultSortOrder,
    onClose,
}: SliderFormModalProps) {
    const isEdit = slider !== null;

    const form = useForm<SliderFormData>({
        title: slider?.title ?? '',
        subtitle: slider?.subtitle ?? '',
        button_text: slider?.button_text ?? '',
        button_link: slider?.button_link ?? '',
        sort_order: String(slider?.sort_order ?? defaultSortOrder),
        status: slider?.status ?? STATUS_ACTIVE,
        image: null,
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

    const currentImage = preview ?? slider?.image_url ?? null;

    const handleImageChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;

        form.setData('image', file);
        setPreview(file ? URL.createObjectURL(file) : null);
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const options = { forceFormData: true, preserveScroll: true, onSuccess: onClose };

        if (isEdit) {
            // Multipart bodies need POST; Laravel reads the spoofed method.
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(update(slider.id).url, options);

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
                    {isEdit ? 'Edit slider' : 'Add slider'}
                </h2>

                <div>
                    <Label>{isEdit ? 'Image' : 'Image (required)'}</Label>
                    <div className="mb-3 flex h-28 items-center justify-center overflow-hidden rounded-lg bg-gray-100 text-xs text-gray-400 dark:bg-white/5">
                        {currentImage ? (
                            <img
                                src={currentImage}
                                alt=""
                                width={480}
                                height={200}
                                className="h-full w-full object-cover"
                            />
                        ) : (
                            'No image'
                        )}
                    </div>
                    <FileInput
                        accept="image/jpeg,image/png,image/webp"
                        aria-label="Slider image"
                        onChange={handleImageChange}
                    />
                    <p className="mt-1.5 text-xs text-gray-500">
                        Wide image, at least 1920x800, max 2MB
                    </p>
                    {form.errors.image && (
                        <p className="mt-1.5 text-xs text-error-500">{form.errors.image}</p>
                    )}
                </div>

                <div>
                    <Label htmlFor="slider-title">Title</Label>
                    <Input
                        id="slider-title"
                        value={form.data.title}
                        onChange={(event) => form.setData('title', event.target.value)}
                        placeholder="Smart Kitchen Essentials"
                        error={Boolean(form.errors.title)}
                        hint={form.errors.title}
                    />
                </div>

                <div>
                    <Label htmlFor="slider-subtitle">Subtitle (optional)</Label>
                    <Input
                        id="slider-subtitle"
                        value={form.data.subtitle}
                        onChange={(event) => form.setData('subtitle', event.target.value)}
                        error={Boolean(form.errors.subtitle)}
                        hint={form.errors.subtitle}
                    />
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <Label htmlFor="slider-button-text">Button text (optional)</Label>
                        <Input
                            id="slider-button-text"
                            value={form.data.button_text}
                            onChange={(event) => form.setData('button_text', event.target.value)}
                            placeholder="Shop Now"
                            error={Boolean(form.errors.button_text)}
                            hint={form.errors.button_text}
                        />
                    </div>
                    <div>
                        <Label htmlFor="slider-button-link">Button link (optional)</Label>
                        <Input
                            id="slider-button-link"
                            value={form.data.button_link}
                            onChange={(event) => form.setData('button_link', event.target.value)}
                            placeholder="/search"
                            error={Boolean(form.errors.button_link)}
                            hint={
                                form.errors.button_link ??
                                'A path like /search or a full https:// link. The button shows only when set.'
                            }
                        />
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <Label htmlFor="slider-order">Sort order</Label>
                        <Input
                            id="slider-order"
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
                        {form.processing ? 'Saving...' : isEdit ? 'Save changes' : 'Create slider'}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}

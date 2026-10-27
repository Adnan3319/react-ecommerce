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
import { store, update } from '@/routes/admin/pages';
import { STATUS_ACTIVE } from '@/shared/constants/status';
import type { ActiveStatus, StatusOption } from '@/shared/types';
import type { Page } from './types';

type PageFormModalProps = {
    page: Page | null;
    statuses: StatusOption[];
    onClose: () => void;
};

type PageFormData = {
    title: string;
    sub_title: string;
    slug: string;
    content: string;
    status: ActiveStatus;
    image: File | null;
    remove_image: boolean;
};

const noop = () => undefined;

// Helper function to convert title to slug
const generateSlug = (text: string) => {
    return text
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
};

export default function PageFormModal({
    page,
    statuses,
    onClose,
}: PageFormModalProps) {
    const isEdit = page !== null;

    const form = useForm<PageFormData>({
        title: page?.title ?? '',
        sub_title: page?.sub_title ?? '',
        slug: page?.slug ?? '',
        content: page?.content ?? '',
        status: page?.status ?? STATUS_ACTIVE,
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

    const currentImage =
        preview ?? (form.data.remove_image ? null : (page?.image_url ?? null));

    const handleImageChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;
        form.setData((data) => ({ ...data, image: file, remove_image: false }));
        setPreview(file ? URL.createObjectURL(file) : null);
    };

    const handleTitleChange = (event: ChangeEvent<HTMLInputElement>) => {
        const newTitle = event.target.value;
        
        // Auto-generate slug when creating a new page, or if editing and slug is empty
        if (!isEdit) {
            form.setData((data) => ({
                ...data,
                title: newTitle,
                slug: generateSlug(newTitle),
            }));
        } else {
            form.setData('title', newTitle);
        }
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const options = {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: onClose,
        };

        form.transform((data) => ({
            ...data,
            ...(isEdit ? { _method: 'put' } : {}),
        }));

        form.post(isEdit ? update(page.id).url : store().url, options);
    };

    return (
        <Modal
            isOpen
            onClose={form.processing ? noop : onClose}
            className="m-4 max-h-[90vh] max-w-2xl overflow-y-auto p-6 sm:p-8 z-[9999]"
        >
            <form onSubmit={submit} noValidate className="space-y-4">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">
                    {isEdit ? 'Edit page' : 'Add page'}
                </h2>

                <div>
                    <Label htmlFor="page-title">Title</Label>
                    <Input
                        id="page-title"
                        value={form.data.title}
                        onChange={handleTitleChange}
                        placeholder="About Us"
                        error={Boolean(form.errors.title)}
                        hint={form.errors.title}
                    />
                </div>

                <div>
                    <Label htmlFor="page-subtitle">Sub Title (optional)</Label>
                    <Input
                        id="page-subtitle"
                        value={form.data.sub_title}
                        onChange={(event) =>
                            form.setData('sub_title', event.target.value)
                        }
                        placeholder="Learn more about our company"
                        error={Boolean(form.errors.sub_title)}
                        hint={form.errors.sub_title}
                    />
                </div>

                <div>
                    <Label htmlFor="page-slug">Slug (URL name)</Label>
                    <Input
                        id="page-slug"
                        value={form.data.slug}
                        onChange={(event) =>
                            form.setData('slug', event.target.value)
                        }
                        placeholder="about-us"
                        error={Boolean(form.errors.slug)}
                        hint={
                            form.errors.slug ??
                            'Leave empty to create it automatically.'
                        }
                    />
                </div>

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
                                aria-label="Page image"
                                onChange={handleImageChange}
                            />
                            {isEdit && page?.image_url && !form.data.image && (
                                <Checkbox
                                    label="Remove current image"
                                    checked={form.data.remove_image}
                                    onChange={(checked) =>
                                        form.setData('remove_image', checked)
                                    }
                                />
                            )}
                        </div>
                    </div>
                </div>

                <div>
                    <Label>Content (optional)</Label>
                    <TextArea
                        rows={4}
                        value={form.data.content}
                        onChange={(value) => form.setData('content', value)}
                        placeholder="Page full content..."
                        error={Boolean(form.errors.content)}
                        hint={form.errors.content}
                    />
                </div>

                <div>
                    <Label>Status</Label>
                    <Select
                        options={statuses}
                        defaultValue={form.data.status}
                        placeholder="Select status"
                        onChange={(value) =>
                            form.setData('status', value as ActiveStatus)
                        }
                    />
                </div>

                <div className="flex justify-end gap-3 pt-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={onClose}
                        disabled={form.processing}
                    >
                        Cancel
                    </Button>
                    <Button type="submit" size="sm" disabled={form.processing}>
                        {form.processing
                            ? 'Saving...'
                            : isEdit
                              ? 'Save changes'
                              : 'Create page'}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
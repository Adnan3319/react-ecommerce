import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Input from '@admin/components/form/input/InputField';
import TextArea from '@admin/components/form/input/TextArea';
import Label from '@admin/components/form/Label';
import Select from '@admin/components/form/Select';
import Button from '@admin/components/ui/button/Button';
import { Modal } from '@admin/components/ui/modal';
import { store, update } from '@/routes/admin/testimonials';
import { STATUS_ACTIVE } from '@/shared/constants/status';
import type { ActiveStatus, StatusOption } from '@/shared/types';
import { DEFAULT_RATING, DEFAULT_SOURCE } from './constants';
import StarRatingInput from './StarRatingInput';
import type {
    SourceOption,
    Testimonial,
    TestimonialLimits,
    TestimonialSource,
} from './types';

type TestimonialFormModalProps = {
    testimonial: Testimonial | null;
    statuses: StatusOption[];
    sources: SourceOption[];
    limits: TestimonialLimits;
    defaultSortOrder: number;
    onClose: () => void;
};

type TestimonialFormData = {
    customer_name: string;
    text: string;
    rating: number;
    source: TestimonialSource;
    sort_order: string;
    status: ActiveStatus;
};

const noop = () => undefined;

/** Mounted only while open, so every open starts from fresh form state. */
export default function TestimonialFormModal({
    testimonial,
    statuses,
    sources,
    limits,
    defaultSortOrder,
    onClose,
}: TestimonialFormModalProps) {
    const isEdit = testimonial !== null;

    const form = useForm<TestimonialFormData>({
        customer_name: testimonial?.customer_name ?? '',
        text: testimonial?.text ?? '',
        rating: testimonial?.rating ?? DEFAULT_RATING,
        source: testimonial?.source ?? DEFAULT_SOURCE,
        sort_order: String(testimonial?.sort_order ?? defaultSortOrder),
        status: testimonial?.status ?? STATUS_ACTIVE,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const options = { preserveScroll: true, onSuccess: onClose };

        if (isEdit) {
            form.put(update(testimonial.id).url, options);

            return;
        }

        form.post(store().url, options);
    };

    return (
        <Modal
            isOpen
            onClose={form.processing ? noop : onClose}
            className="m-4 max-h-[90vh] max-w-xl overflow-y-auto p-6 sm:p-8"
        >
            <form onSubmit={submit} noValidate className="space-y-4">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">
                    {isEdit ? 'Edit testimonial' : 'Add testimonial'}
                </h2>
                <p className="text-xs text-gray-500">
                    Only add real feedback from customers who agreed to have it
                    shown, such as a Facebook comment or a message.
                </p>

                <div>
                    <Label htmlFor="testimonial-name">Customer name</Label>
                    <Input
                        id="testimonial-name"
                        value={form.data.customer_name}
                        onChange={(event) =>
                            form.setData('customer_name', event.target.value)
                        }
                        error={Boolean(form.errors.customer_name)}
                        hint={form.errors.customer_name}
                    />
                </div>

                <div>
                    <Label htmlFor="testimonial-text">Feedback</Label>
                    <TextArea
                        value={form.data.text}
                        rows={4}
                        onChange={(value) => form.setData('text', value)}
                        error={Boolean(form.errors.text)}
                        hint={form.errors.text}
                    />
                    <p
                        className="mt-1 text-end text-xs text-gray-500"
                        aria-live="polite"
                    >
                        {form.data.text.length}/{limits.text}
                    </p>
                </div>

                <div>
                    <Label>Rating</Label>
                    <StarRatingInput
                        value={form.data.rating}
                        min={limits.min_rating}
                        max={limits.max_rating}
                        onChange={(rating) => form.setData('rating', rating)}
                    />
                    {form.errors.rating && (
                        <p className="text-error-500 mt-1.5 text-xs">
                            {form.errors.rating}
                        </p>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <div>
                        <Label>Source</Label>
                        <Select
                            options={sources}
                            defaultValue={form.data.source}
                            placeholder="Select source"
                            onChange={(value) =>
                                form.setData(
                                    'source',
                                    value as TestimonialSource,
                                )
                            }
                        />
                        {form.errors.source && (
                            <p className="text-error-500 mt-1.5 text-xs">
                                {form.errors.source}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="testimonial-order">Sort order</Label>
                        <Input
                            id="testimonial-order"
                            type="number"
                            min={0}
                            value={form.data.sort_order}
                            onChange={(event) =>
                                form.setData('sort_order', event.target.value)
                            }
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
                            onChange={(value) =>
                                form.setData('status', value as ActiveStatus)
                            }
                        />
                        {form.errors.status && (
                            <p className="text-error-500 mt-1.5 text-xs">
                                {form.errors.status}
                            </p>
                        )}
                    </div>
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
                              : 'Add testimonial'}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}

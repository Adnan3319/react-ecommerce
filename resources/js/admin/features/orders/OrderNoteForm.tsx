import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Input from '@admin/components/form/input/InputField';
import Button from '@admin/components/ui/button/Button';
import { notes } from '@/routes/admin/orders';
import { NOTE_MAX_LENGTH } from './constants';

export default function OrderNoteForm({ orderId }: { orderId: number }) {
    const form = useForm({ note: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(notes(orderId).url, { preserveScroll: true, onSuccess: () => form.reset() });
    };

    return (
        <form onSubmit={submit} className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start">
            <div className="flex-1">
                <Input
                    value={form.data.note}
                    maxLength={NOTE_MAX_LENGTH}
                    onChange={(event) => form.setData('note', event.target.value)}
                    placeholder='Add a note (e.g. "Called, will receive after 5pm")'
                    aria-label="Note"
                    error={Boolean(form.errors.note)}
                    hint={form.errors.note}
                />
            </div>
            <Button type="submit" variant="outline" size="sm" disabled={form.processing || form.data.note.trim() === ''}>
                Add note
            </Button>
        </form>
    );
}

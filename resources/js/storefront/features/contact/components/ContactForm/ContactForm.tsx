import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Button, Input, Textarea } from '@/storefront/components/ui';
import { validateContact } from '../../schemas';
import type { ContactValues } from '../../schemas';

export default function ContactForm() {
    const form = useForm<ContactValues>({ name: '', phone: '', message: '' });
    const [isPreviewShown, setIsPreviewShown] = useState(false);

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const errors = validateContact(form.data);
        form.clearErrors();

        if (Object.keys(errors).length > 0) {
            form.setError(errors);
            setIsPreviewShown(false);

            return;
        }

        // Messages are not connected to a backend yet.
        setIsPreviewShown(true);
    }

    return (
        <form
            onSubmit={handleSubmit}
            noValidate
            aria-label="Send us a message"
            className="flex flex-col gap-4 rounded-card border border-neutral-200 bg-white p-5 shadow-card sm:p-6"
        >
            <h2 className="text-lg font-bold tracking-tight text-neutral-900">
                Send us a message
            </h2>

            <Input
                label="Your name"
                name="name"
                autoComplete="name"
                value={form.data.name}
                error={form.errors.name}
                onChange={(event) => form.setData('name', event.target.value)}
            />
            <Input
                label="Mobile number"
                name="phone"
                type="tel"
                inputMode="numeric"
                autoComplete="tel"
                placeholder="01XXXXXXXXX"
                value={form.data.phone}
                error={form.errors.phone}
                onChange={(event) => form.setData('phone', event.target.value)}
            />
            <Textarea
                label="Message"
                name="message"
                rows={5}
                value={form.data.message}
                error={form.errors.message}
                onChange={(event) =>
                    form.setData('message', event.target.value)
                }
            />

            <Button type="submit" size="lg" fullWidth>
                Send Message
            </Button>

            {isPreviewShown && (
                <p
                    role="status"
                    className="rounded-control bg-accent-50 p-3 text-sm text-accent-700"
                >
                    Your message looks good. Messaging is not live yet, so it
                    was not sent.
                </p>
            )}
        </form>
    );
}

import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { store } from '@/routes/contact';
import { Button, Input, Textarea } from '@/storefront/components/ui';
import { MAX_MESSAGE_LENGTH, validateContact } from '../../schemas';
import type { ContactValues } from '../../schemas';

export default function ContactForm() {
    const { flash } = usePage();
    const form = useForm<ContactValues>({
        name: '',
        phone: '',
        email: '',
        message: '',
        website: '',
    });

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const errors = validateContact(form.data);
        form.clearErrors();

        if (Object.keys(errors).length > 0) {
            form.setError(errors);

            return;
        }

        form.post(store().url, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
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
            <Input
                label="Email (optional)"
                name="email"
                type="email"
                autoComplete="email"
                value={form.data.email}
                error={form.errors.email}
                onChange={(event) => form.setData('email', event.target.value)}
            />
            <Textarea
                label="Message"
                name="message"
                rows={5}
                maxLength={MAX_MESSAGE_LENGTH}
                value={form.data.message}
                error={form.errors.message}
                onChange={(event) =>
                    form.setData('message', event.target.value)
                }
            />

            {/* Honeypot: hidden from people and assistive tech, bots fill it in. */}
            <div aria-hidden="true" className="absolute -start-[9999px]">
                <label>
                    Website
                    <input
                        type="text"
                        name="website"
                        tabIndex={-1}
                        autoComplete="off"
                        value={form.data.website}
                        onChange={(event) =>
                            form.setData('website', event.target.value)
                        }
                    />
                </label>
            </div>

            <Button type="submit" size="lg" fullWidth loading={form.processing}>
                Send Message
            </Button>

            {form.wasSuccessful && typeof flash.success === 'string' && (
                <p
                    role="status"
                    className="rounded-control bg-accent-50 p-3 text-sm text-accent-700"
                >
                    {flash.success}
                </p>
            )}
        </form>
    );
}

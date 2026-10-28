import { Head } from '@inertiajs/react';
import {
    ContactDetails,
    ContactForm,
    ContactMap,
} from '@/storefront/features/contact';

export default function Contact() {
    return (
        <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
            <Head title="Contact Us" />

            <header className="mb-8">
                <h1 className="text-3xl font-bold tracking-tight text-neutral-900">
                    Contact Us
                </h1>
                <p className="mt-2 text-neutral-600">
                    Have a question about a product or your order? Send us a
                    message or reach us using the details below.
                </p>
            </header>

            <div className="grid items-start gap-8 md:grid-cols-2">
                <div className="flex flex-col gap-4">
                    <ContactDetails />
                    <ContactMap />
                </div>
                <ContactForm />
            </div>
        </div>
    );
}

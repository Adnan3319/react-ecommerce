import { Head } from '@inertiajs/react';
import { home } from '@/routes';
import { Breadcrumbs } from '@/storefront/components/Breadcrumbs';
import { ContactDetails, ContactForm } from '@/storefront/features/contact';

export default function Contact() {
    return (
        <>
            <Head title="Contact Us">
                <meta
                    name="description"
                    content="Get in touch with us for questions about products or orders."
                />
            </Head>

            <div className="mx-auto max-w-7xl px-4 pt-6 pb-12 sm:px-6">
                <Breadcrumbs
                    items={[
                        { label: 'Home', href: home() },
                        { label: 'Contact' },
                    ]}
                />

                <header className="mt-4 mb-8 max-w-2xl">
                    <h1 className="text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl">
                        Contact Us
                    </h1>
                    <p className="mt-3 text-neutral-600">
                        Have a question about a product or your order? Send us a
                        message or reach us using the details below.
                    </p>
                </header>

                <div className="grid gap-8 lg:grid-cols-[1fr_1.2fr]">
                    <ContactDetails />
                    <ContactForm />
                </div>
            </div>
        </>
    );
}

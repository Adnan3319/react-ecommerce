import { Head } from '@inertiajs/react';
import { home } from '@/routes';
import { Breadcrumbs } from '@/storefront/components/Breadcrumbs';
import { AboutIntro, AboutValues } from '@/storefront/features/about';

export default function About() {
    return (
        <>
            <Head title="About Us">
                <meta
                    name="description"
                    content="Learn about our online store for kitchenware and ladies fashion."
                />
            </Head>

            <div className="mx-auto max-w-7xl px-4 pt-6 pb-12 sm:px-6">
                <Breadcrumbs
                    items={[
                        { label: 'Home', href: home() },
                        { label: 'About Us' },
                    ]}
                />
                <div className="mt-4">
                    <AboutIntro />
                    <AboutValues />
                </div>
            </div>
        </>
    );
}

import { Link } from '@inertiajs/react';
import { search } from '@/routes';
import { Icon, ImagePlaceholder } from '@/shared/ui';

export default function PromoBanner() {
    return (
        <section className="mx-auto max-w-7xl px-4 pt-10 sm:px-6">
            <div className="grid items-center gap-6 overflow-hidden rounded-card bg-cream-100 p-6 sm:p-10 md:grid-cols-2">
                <div>
                    <p className="text-sm font-medium text-neutral-600">
                        Special Offer
                    </p>
                    <h2 className="mt-2 text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl">
                        Up to 50% Off
                    </h2>
                    <p className="mt-3 max-w-sm text-sm text-neutral-600">
                        Limited time offer on selected items. Hurry up and grab
                        the best deals!
                    </p>
                    <Link
                        href={search()}
                        className="mt-5 inline-flex h-11 items-center gap-2 rounded-control bg-accent-600 px-5 text-sm font-semibold text-white transition-colors hover:bg-accent-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600"
                    >
                        Shop the Sale
                        <Icon name="arrowRight" size={16} />
                    </Link>
                </div>
                <ImagePlaceholder className="hidden aspect-video w-full rounded-card bg-white/60 md:flex" />
            </div>
        </section>
    );
}

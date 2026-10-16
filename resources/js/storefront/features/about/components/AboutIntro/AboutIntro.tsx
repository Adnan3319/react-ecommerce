import { Link } from '@inertiajs/react';
import { search } from '@/routes';
import { Icon } from '@/shared/ui';

export default function AboutIntro() {
    return (
        <section className="overflow-hidden rounded-card bg-cream-100 px-6 py-12 text-center sm:px-12 sm:py-16">
            <p className="text-sm font-semibold tracking-widest text-accent-600 uppercase">
                About Us
            </p>
            <h1 className="mx-auto mt-3 max-w-2xl text-3xl font-bold tracking-tight text-neutral-900 sm:text-5xl">
                Everyday kitchenware and fashion, made simple
            </h1>
            <p className="mx-auto mt-5 max-w-2xl text-base text-neutral-600">
                Checkit is an online store for useful kitchen essentials and
                ladies fashion. We want shopping to be easy: clear prices,
                honest photos and delivery right to your door.
            </p>
            <Link
                href={search()}
                className="mt-7 inline-flex h-11 items-center gap-2 rounded-control bg-accent-600 px-6 text-sm font-semibold text-white transition-colors hover:bg-accent-700"
            >
                Start shopping
                <Icon name="arrowRight" size={16} />
            </Link>
        </section>
    );
}

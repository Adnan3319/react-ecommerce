import { Link } from '@inertiajs/react';
import { search } from '@/routes';
import { Icon, ImagePlaceholder } from '@/storefront/components/ui';
import { PROMO_PERKS } from '../../static-data';

export default function PromoBanner() {
    return (
        <section className="mx-auto max-w-7xl px-4 pt-12 sm:px-6">
            <div className="grid items-center gap-6 overflow-hidden rounded-card bg-linear-to-br from-cream-100 to-accent-50 p-6 sm:p-10 lg:grid-cols-[1fr_1.2fr_auto]">
                <div>
                    <span className="inline-block rounded-full bg-accent-100 px-3 py-1 text-xs font-semibold tracking-wide text-accent-700 uppercase">
                        Limited time offer
                    </span>
                    <h2 className="mt-4 text-3xl font-extrabold tracking-tight text-neutral-900 sm:text-4xl">
                        Up to 50% Off
                    </h2>
                    <p className="mt-3 max-w-sm text-sm text-neutral-600">
                        Premium kitchenware and fashion, handpicked for your
                        home and lifestyle.
                    </p>
                    <Link
                        href={search()}
                        className="mt-5 inline-flex h-11 items-center gap-2 rounded-full btn-accent px-6 text-sm font-semibold text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600"
                    >
                        Shop the Sale
                        <Icon name="arrowRight" size={16} />
                    </Link>
                </div>

                <ImagePlaceholder className="hidden aspect-video w-full rounded-card bg-white/60 lg:flex" />

                <ul className="grid gap-4 rounded-card bg-white/70 p-5 ring-1 ring-white backdrop-blur-sm sm:grid-cols-3 lg:grid-cols-1">
                    {PROMO_PERKS.map((perk) => (
                        <li
                            key={perk.title}
                            className="flex items-center gap-3"
                        >
                            <Icon
                                name={perk.icon}
                                size={24}
                                className="shrink-0 text-accent-600"
                            />
                            <div>
                                <p className="text-sm font-semibold text-neutral-900">
                                    {perk.title}
                                </p>
                                <p className="text-xs text-neutral-600">
                                    {perk.description}
                                </p>
                            </div>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}

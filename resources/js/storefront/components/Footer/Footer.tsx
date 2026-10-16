import { Link, usePage } from '@inertiajs/react';
import { about, cart, contact, home, search } from '@/routes';
import type { SharedProps } from '@/shared/types';
import { Image } from '@/shared/ui';
import { LOGO } from '@/storefront/config/site';
import { CATEGORY_LINKS } from '../navigation';

const QUICK_LINKS = [
    { label: 'Home', href: home() },
    { label: 'Shop', href: search() },
    { label: 'Cart', href: cart() },
    { label: 'About Us', href: about() },
    { label: 'Contact', href: contact() },
];

const LINK_CLASS = 'transition-colors hover:text-accent-500';

export default function Footer() {
    const { name } = usePage<SharedProps>().props;

    return (
        <footer className="bg-ink">
            <div className="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-2 sm:px-6 lg:grid-cols-4">
                <div>
                    <Link
                        href={home()}
                        aria-label={name}
                        className="inline-block rounded-control bg-white p-2"
                    >
                        <Image
                            src={LOGO.src}
                            alt={name}
                            width={LOGO.width}
                            height={LOGO.height}
                            className="h-12 w-auto"
                        />
                    </Link>
                    <p className="mt-3 max-w-xs text-sm text-neutral-300">
                        Quality kitchenware and fashion, delivered to your door.
                    </p>
                </div>

                <nav aria-label="Quick links">
                    <h2 className="text-sm font-semibold text-white">
                        Quick Links
                    </h2>
                    <ul className="mt-3 space-y-2 text-sm text-neutral-300">
                        {QUICK_LINKS.map((link) => (
                            <li key={link.label}>
                                <Link href={link.href} className={LINK_CLASS}>
                                    {link.label}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </nav>

                <nav aria-label="Categories">
                    <h2 className="text-sm font-semibold text-white">
                        Categories
                    </h2>
                    <ul className="mt-3 space-y-2 text-sm text-neutral-300">
                        {CATEGORY_LINKS.map((category) => (
                            <li key={category.slug}>
                                <Link
                                    href={category.href}
                                    className={LINK_CLASS}
                                >
                                    {category.label}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </nav>

                <div>
                    <h2 className="text-sm font-semibold text-white">
                        Payment
                    </h2>
                    <p className="mt-3 text-sm text-neutral-300">
                        Cash on Delivery. Pay when your order arrives.
                    </p>
                </div>
            </div>

            <div className="border-t border-white/10">
                <p className="mx-auto max-w-7xl px-4 py-4 text-xs text-neutral-400 sm:px-6">
                    &copy; {name}. All rights reserved.
                </p>
            </div>
        </footer>
    );
}

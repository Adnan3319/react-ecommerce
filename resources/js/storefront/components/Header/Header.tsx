import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { home } from '@/routes';
import type { SharedProps } from '@/shared/types';
import { Icon, Image } from '@/shared/ui';
import { LOGO } from '@/storefront/config/site';
import { CartLink } from '../CartLink';
import { MAIN_NAV_ID, MainNav } from '../MainNav';
import { SearchBar } from '../SearchBar';

export default function Header() {
    const { name } = usePage<SharedProps>().props;
    const [isMenuOpen, setIsMenuOpen] = useState(false);

    return (
        <header className="sticky top-0 z-40 bg-white shadow-card">
            <div className="mx-auto grid max-w-7xl grid-cols-[1fr_auto] items-center gap-x-4 gap-y-3 px-4 py-3 sm:px-6 md:grid-cols-[auto_1fr_auto] md:gap-x-10 md:py-4">
                <Link href={home()} aria-label={name} className="shrink-0">
                    <Image
                        src={LOGO.src}
                        alt={name}
                        width={LOGO.width}
                        height={LOGO.height}
                        priority
                        className="h-12 w-auto md:h-14"
                    />
                </Link>

                <div className="order-last col-span-2 md:order-none md:col-span-1 md:mx-auto md:w-full md:max-w-2xl">
                    <SearchBar />
                </div>

                <div className="flex items-center gap-1">
                    <CartLink />
                    <button
                        type="button"
                        aria-label={isMenuOpen ? 'Close menu' : 'Open menu'}
                        aria-expanded={isMenuOpen}
                        aria-controls={MAIN_NAV_ID}
                        onClick={() => setIsMenuOpen((open) => !open)}
                        className="flex h-11 w-11 items-center justify-center rounded-control text-neutral-800 transition-colors hover:bg-neutral-100 md:hidden"
                    >
                        <Icon name={isMenuOpen ? 'close' : 'menu'} size={26} />
                    </button>
                </div>
            </div>

            <MainNav
                isOpen={isMenuOpen}
                onNavigate={() => setIsMenuOpen(false)}
            />
        </header>
    );
}

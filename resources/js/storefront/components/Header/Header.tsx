import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { home } from '@/routes';
import { cn } from '@/shared/lib';
import type { SharedProps } from '@/shared/types';
import { Icon, Image } from '@/storefront/components/ui';
import { LOGO } from '@/storefront/config/site';
import { CartLink } from '../CartLink';
import { MAIN_NAV_ID, MainNav } from '../MainNav';
import HeaderSearch, { HEADER_SEARCH_INPUT_ID } from '../HeaderSearch';

/** Scroll distance (px) after which the home header turns solid. */
const SCROLL_SOLID_OFFSET = 24;

export default function Header() {
    const { name } = usePage<SharedProps>().props;
    const { url } = usePage();
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    // On the home page the glass header floats over the hero (desktop).
    const isHome = url.split('?')[0] === home().url;
    const [isScrolled, setIsScrolled] = useState(false);
    // On mobile the search field is hidden until the search icon opens it.
    const [isSearchOpen, setIsSearchOpen] = useState(false);

    useEffect(() => {
        function syncScroll(): void {
            setIsScrolled(window.scrollY > SCROLL_SOLID_OFFSET);
        }

        syncScroll();
        window.addEventListener('scroll', syncScroll, { passive: true });

        return () => window.removeEventListener('scroll', syncScroll);
    }, []);

    function toggleSearch(): void {
        const next = !isSearchOpen;

        setIsSearchOpen(next);

        if (next) {
            requestAnimationFrame(() =>
                document.getElementById(HEADER_SEARCH_INPUT_ID)?.focus(),
            );
        }
    }

    return (
        <header
            className={cn(
                'inset-x-0 top-0 z-40 border-b border-white/10 bg-linear-to-b from-white/10 to-transparent text-white shadow-[inset_0_1px_0_0_rgb(255_255_255/0.15)] backdrop-blur-xl',
                isHome ? 'sticky md:fixed' : 'sticky',
                isHome && !isScrolled ? 'bg-ink/70 md:bg-ink/25' : 'bg-ink/90',
            )}
        >
            <div className="mx-auto grid max-w-7xl grid-cols-[1fr_auto] items-center gap-x-4 gap-y-3 px-4 py-3 sm:px-6 md:grid-cols-[auto_1fr_16rem_auto] md:gap-x-8">
                <Link
                    href={home()}
                    aria-label={name}
                    className="order-1 shrink-0 md:order-none"
                >
                    <Image
                        src={LOGO.lightSrc}
                        alt={name}
                        width={LOGO.width}
                        height={LOGO.height}
                        priority
                        className="h-11 w-auto"
                    />
                </Link>

                <MainNav
                    isOpen={isMenuOpen}
                    onNavigate={() => setIsMenuOpen(false)}
                />

                <div
                    className={cn(
                        'order-3 col-span-2 md:order-none md:col-span-1',
                        !isSearchOpen && 'hidden md:block',
                    )}
                >
                    <HeaderSearch onNavigate={() => setIsSearchOpen(false)} />
                </div>

                <div className="order-2 flex items-center gap-2 md:order-none">
                    <button
                        type="button"
                        aria-label={
                            isSearchOpen ? 'Close search' : 'Open search'
                        }
                        aria-expanded={isSearchOpen}
                        onClick={toggleSearch}
                        className="flex h-11 w-11 items-center justify-center rounded-control text-white transition-colors hover:bg-white/10 md:hidden"
                    >
                        <Icon
                            name={isSearchOpen ? 'close' : 'search'}
                            size={24}
                        />
                    </button>
                    <CartLink />
                    <button
                        type="button"
                        aria-label={isMenuOpen ? 'Close menu' : 'Open menu'}
                        aria-expanded={isMenuOpen}
                        aria-controls={MAIN_NAV_ID}
                        onClick={() => setIsMenuOpen((open) => !open)}
                        className="flex h-11 w-11 items-center justify-center rounded-control text-white transition-colors hover:bg-white/10 md:hidden"
                    >
                        <Icon name={isMenuOpen ? 'close' : 'menu'} size={26} />
                    </button>
                </div>
            </div>
        </header>
    );
}

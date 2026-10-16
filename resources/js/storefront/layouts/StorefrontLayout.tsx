import type { ReactNode } from 'react';
import { Footer } from '@/storefront/components/Footer';
import { Header } from '@/storefront/components/Header';
import { MobileNav } from '@/storefront/components/MobileNav';

type StorefrontLayoutProps = {
    children: ReactNode;
};

export default function StorefrontLayout({ children }: StorefrontLayoutProps) {
    return (
        <div className="flex min-h-screen flex-col bg-white text-neutral-900 selection:bg-accent-100">
            <a
                href="#content"
                className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:rounded-control focus:bg-white focus:px-4 focus:py-2"
            >
                Skip to content
            </a>
            <Header />
            <main id="content" className="flex-1 pb-14 md:pb-0">
                {children}
            </main>
            <Footer />
            <MobileNav />
        </div>
    );
}

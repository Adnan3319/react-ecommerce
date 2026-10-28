import BrandMark from '@admin/components/common/BrandMark';
import GridShape from '@admin/components/common/GridShape';
import ThemeTogglerTwo from '@admin/components/common/ThemeTogglerTwo';
import React from 'react';
import { Link } from '@admin/lib/router';

export default function AuthLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <div className="relative z-1 bg-white p-6 sm:p-0 dark:bg-gray-900">
            <div className="relative flex h-screen w-full flex-col justify-center sm:p-0 lg:flex-row dark:bg-gray-900">
                {children}
                <div className="bg-brand-950 hidden h-full w-full items-center lg:grid lg:w-1/2 dark:bg-white/5">
                    <div className="relative z-1 flex items-center justify-center">
                        <GridShape />

                        <div className="flex max-w-xs flex-col items-center">
                            <Link to="/" className="mb-4 block">
                                <BrandMark nameClassName="text-2xl text-white dark:text-white" />
                            </Link>

                            <p className="text-center text-gray-400 dark:text-white/60">
                                Sign in to manage your store.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="fixed inset-e-6 bottom-6 z-50 hidden sm:block">
                    <ThemeTogglerTwo />
                </div>
            </div>
        </div>
    );
}

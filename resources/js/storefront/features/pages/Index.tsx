import React from 'react';

type PageProps = {
    page: {
        id: number;
        title: string;
        sub_title: string | null;
        slug: string;
        content: string | null;
        image_url: string | null;
    } | null;
};

export default function PageIndex({ page }: PageProps) {
    return (
        <div className="min-h-screen bg-white dark:bg-gray-900">
            {/* Fixed Banner Section */}
            <div className="relative bg-gray-900 py-20 text-center text-white shadow-md">
                <span className="text-xs font-semibold uppercase tracking-widest text-red-500">
                    {page?.title ?? 'Page'}
                </span>
                <h1 className="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">
                    {page?.sub_title ?? 'Dynamic Storefront Page'}
                </h1>
            </div>

            {/* Main Content: Bamdike Content & Dandike Image */}
            <div className="container mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div className="grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
                    
                    {/* Bamdike Content */}
                    <div className="space-y-6">
                        <h2 className="text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl">
                            {page?.title}
                        </h2>

                        <div className="prose dark:prose-invert text-gray-600 dark:text-gray-300 max-w-none text-base leading-relaxed">
                            {page?.content ? (
                                <div dangerouslySetInnerHTML={{ __html: page.content }} />
                            ) : (
                                <p>No content available for this page yet.</p>
                            )}
                        </div>
                    </div>

                    {/* Dandike Image */}
                    <div className="flex justify-center">
                        {page?.image_url ? (
                            <img
                                src={page.image_url}
                                alt={page.title}
                                className="h-auto max-h-[450px] w-full rounded-2xl object-cover shadow-xl"
                            />
                        ) : (
                            <div className="flex h-80 w-full items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                                No image available
                            </div>
                        )}
                    </div>

                </div>
            </div>
        </div>
    );
}
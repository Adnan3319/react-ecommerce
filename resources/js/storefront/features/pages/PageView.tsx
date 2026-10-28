import { Image } from '@/storefront/components/ui';
import type { StaticPage } from './types';

const IMAGE_SIZE = 960;

type PageViewProps = {
    page: StaticPage;
};

/** Fixed banner with the title, then the content: beside the image (6/6) when there is one, full width otherwise. */
export default function PageView({ page }: PageViewProps) {
    const { title, sub_title: subTitle, content, image_url: imageUrl } = page;

    return (
        <>
            <header className="bg-shaded px-4 py-14 text-center text-white sm:py-20">
                <h1 className="text-3xl font-extrabold tracking-tight sm:text-5xl">
                    {title}
                </h1>
                {subTitle && (
                    <p className="mx-auto mt-3 max-w-2xl text-base text-white/80">
                        {subTitle}
                    </p>
                )}
            </header>

            <article className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16">
                <div className="grid grid-cols-1 items-start gap-8 md:grid-cols-12 md:gap-12">
                    {content && (
                        <div
                            className={
                                imageUrl ? 'md:col-span-6' : 'md:col-span-12'
                            }
                        >
                            <div
                                className="text-base leading-relaxed text-neutral-700 [&_a]:text-accent-600 [&_a]:underline [&_h2]:mt-6 [&_h2]:mb-3 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:text-neutral-900 [&_h3]:mt-5 [&_h3]:mb-2 [&_h3]:text-xl [&_h3]:font-semibold [&_h3]:text-neutral-900 [&_li]:mb-1 [&_ol]:mb-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:mb-4 [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-6"
                                dangerouslySetInnerHTML={{ __html: content }}
                            />
                        </div>
                    )}
                    {imageUrl && (
                        <div className="md:col-span-6">
                            <Image
                                src={imageUrl}
                                alt={title}
                                width={IMAGE_SIZE}
                                height={IMAGE_SIZE}
                                priority
                                className="w-full rounded-card object-cover shadow-card"
                            />
                        </div>
                    )}
                </div>
            </article>
        </>
    );
}

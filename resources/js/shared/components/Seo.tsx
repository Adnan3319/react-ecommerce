import { Head } from '@inertiajs/react';
import type { Seo as SeoData } from '@/shared/types';

type SeoProps = {
    seo: SeoData;
};

/**
 * Mirrors the tags rendered server-side in `storefront.blade.php`.
 * `head-key`s must stay identical to the blade `data-inertia` keys so client
 * navigation replaces the tags instead of duplicating them.
 */
export default function Seo({ seo }: SeoProps) {
    const { title, description, canonical, noindex, image, jsonLd } = seo;

    return (
        <Head>
            <title head-key="title">{title}</title>
            <meta
                head-key="robots"
                name="robots"
                content={noindex ? 'noindex,follow' : 'index,follow'}
            />
            {description && (
                <meta
                    head-key="description"
                    name="description"
                    content={description}
                />
            )}
            {canonical && (
                <link head-key="canonical" rel="canonical" href={canonical} />
            )}
            <meta head-key="og:type" property="og:type" content="website" />
            <meta head-key="og:title" property="og:title" content={title} />
            {description && (
                <meta
                    head-key="og:description"
                    property="og:description"
                    content={description}
                />
            )}
            {canonical && (
                <meta head-key="og:url" property="og:url" content={canonical} />
            )}
            {image && (
                <meta head-key="og:image" property="og:image" content={image} />
            )}
            <meta
                head-key="twitter:card"
                name="twitter:card"
                content={image ? 'summary_large_image' : 'summary'}
            />
            {jsonLd.map((schema, index) => (
                <script
                    key={index}
                    head-key={`jsonld-${index}`}
                    type="application/ld+json"
                >
                    {JSON.stringify(schema)}
                </script>
            ))}
        </Head>
    );
}

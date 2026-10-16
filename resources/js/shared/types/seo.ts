export type JsonLd = Record<string, unknown>;

export type Seo = {
    title: string;
    description: string | null;
    canonical: string | null;
    noindex: boolean;
    image: string | null;
    jsonLd: JsonLd[];
};

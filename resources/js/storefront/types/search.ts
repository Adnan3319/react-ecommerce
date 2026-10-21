/** One card of `GET /search/suggest`. The price is in minor units. */
export type Suggestion = {
    name: string;
    category: string | null;
    thumb: string | null;
    price: number | null;
    url: string;
};

export type SuggestResult = {
    items: Suggestion[];
    /** Number of products the full results page lists. */
    total: number;
};

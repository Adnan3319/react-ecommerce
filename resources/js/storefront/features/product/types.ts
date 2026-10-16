export type ProductSummary = {
    id: number;
    slug: string;
    categorySlug: string;
    name: string;
    /** Integer minor units. */
    price: number;
    /** Integer minor units; shown struck through when higher than price. */
    comparePrice: number | null;
    /** Null until the product has reviews. */
    rating: number | null;
    reviewCount: number;
    image: string | null;
};

export type ProductDetail = ProductSummary & {
    images: string[];
    description: string;
};

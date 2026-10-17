export type CartLine = {
    id: number;
    productId: number;
    slug: string;
    name: string;
    image: string | null;
    /** Integer minor units. */
    unitPrice: number;
    quantity: number;
    maxQuantity: number;
};

/** Minimal product data needed to add an item; any product type satisfies it. */
export type CartProduct = {
    id: number;
    slug: string;
    name: string;
    /** Integer minor units. */
    price: number;
    image: string | null;
};

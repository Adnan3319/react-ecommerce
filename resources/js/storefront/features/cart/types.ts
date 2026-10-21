/** One line of `Services\Cart\CartService::lines()`. Amounts are integer minor units. */
export type CartLine = {
    variant_id: number;
    product_name: string;
    slug: string;
    variant_label: string | null;
    image_url: string | null;
    unit_price: number;
    quantity: number;
    line_total: number;
    stock: number;
    max_quantity: number;
    price_changed: boolean;
    previous_price: number;
    unavailable: boolean;
};

/** The `miniCart` prop (`HandleInertiaRequests::miniCart()`): only present after a partial reload that names it. */
export type MiniCartData = {
    lines: CartLine[];
    /** Minor units, payable lines only. */
    subtotal: number;
    maxQty: number;
};

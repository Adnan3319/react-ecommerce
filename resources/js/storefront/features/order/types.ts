/** `Storefront\OrderSuccessResource`: what the thank-you page shows. Amounts are minor units. */
export type PlacedOrder = {
    number: string;
    first_name: string;
    phone: string;
    subtotal: number;
    delivery_charge: number;
    delivery_zone_label: string;
    total: number;
    items: {
        name: string;
        variant_label: string | null;
        quantity: number;
        line_total: number;
        image_url: string | null;
    }[];
};

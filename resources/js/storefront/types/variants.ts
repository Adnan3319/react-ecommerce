export type StockState = 'in' | 'low' | 'out';

export type PageHead = {
    id: number;
    name: string;
    values: { id: number; value: string }[];
};

/** The fields of a variant the pickers need; the product page and the quick-add options both send them. */
export type PageVariant = {
    id: number;
    /** One value id per head, in head order. */
    value_ids: number[];
    price: number;
    sale_price: number | null;
    effective_price: number;
    discount_percent: number | null;
    stock: number;
    stock_state: StockState;
};

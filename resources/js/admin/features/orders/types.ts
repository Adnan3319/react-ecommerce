import type { Paginated } from '@/shared/types';

export type BadgeColor = 'primary' | 'success' | 'error' | 'warning' | 'info' | 'light' | 'dark';

/** Mirrors App\Enums\OrderStatus (color is OrderStatus::color(), a Badge color). */
export type OrderStatusOption = {
    value: string;
    label: string;
    color: BadgeColor;
};

export type OrderStatusTab = OrderStatusOption & { count: number };

export type SourceOption = {
    value: string;
    label: string;
};

export type OrderListItem = {
    id: number;
    number: string;
    placed_at: string;
    customer_name: string;
    customer_phone: string;
    delivery_zone_label: string;
    items_count: number;
    total: string;
    source: SourceOption;
    status: OrderStatusOption;
};

export type OrderListFilters = {
    search: string | null;
    order_status: string | null;
    source: string | null;
    range: string | null;
    per_page: number;
};

export type OrderIndexProps = {
    orders: Paginated<OrderListItem>;
    filters: OrderListFilters;
    statuses: OrderStatusTab[];
    sources: SourceOption[];
};

export type OrderItemLine = {
    id: number;
    name: string;
    variant_label: string | null;
    sku: string;
    quantity: number;
    unit_price: string;
    line_total: string;
    image_url: string | null;
};

export type OrderCustomer = {
    id: number;
    name: string;
    phone: string;
    address: string;
    is_blocked: boolean;
};

export type CustomerHistoryCounts = {
    delivered: number;
    cancelled: number;
    pending: number;
};

export type HistoryKind = 'placed' | 'note' | 'change';

export type HistoryEntry = {
    id: number;
    kind: HistoryKind;
    from: string | null;
    to: string;
    note: string | null;
    by: string | null;
    at: string;
};

export type OrderDetailData = {
    id: number;
    number: string;
    placed_at: string;
    status: OrderStatusOption;
    payment_status: string;
    source: SourceOption;
    utm_source: string | null;
    utm_medium: string | null;
    utm_campaign: string | null;
    fbclid: string | null;
    customer_note: string | null;
    subtotal: string;
    delivery_charge: string;
    delivery_zone_label: string;
    total: string;
    items: OrderItemLine[];
    customer: OrderCustomer;
    customer_history: CustomerHistoryCounts;
    history: HistoryEntry[];
    allowed_next: OrderStatusOption[];
};

export type OrderShowProps = {
    order: OrderDetailData;
};

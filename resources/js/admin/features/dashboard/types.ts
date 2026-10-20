/** Props of `Admin\DashboardController` (`Services\Dashboard\DashboardService::summary()`). */
export type DashboardProps = {
    today: {
        orders: number;
        /** Delivered today, formatted money. */
        revenue: string;
        /** Every pending order right now. */
        pending: number;
    };
    trend: { date: string; orders: number }[];
    outcome: {
        delivered: number;
        cancelled: number;
        delivered_percent: number | null;
    };
    recent: {
        id: number;
        number: string;
        customer_name: string;
        total: string;
        placed_at: string;
        status: { value: string; label: string; color: BadgeColor };
    }[];
    low_stock: {
        variant_id: number;
        product_id: number;
        product_name: string;
        variant_label: string | null;
        sku: string;
        stock: number;
    }[];
    low_stock_threshold: number;
};

export type BadgeColor =
    | 'primary'
    | 'success'
    | 'error'
    | 'warning'
    | 'info'
    | 'light'
    | 'dark';

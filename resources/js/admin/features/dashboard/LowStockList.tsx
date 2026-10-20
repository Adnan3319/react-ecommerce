import { Link } from '@inertiajs/react';
import Badge from '@admin/components/ui/badge/Badge';
import { edit } from '@/routes/admin/products';
import type { DashboardProps } from './types';

type LowStockListProps = Pick<
    DashboardProps,
    'low_stock' | 'low_stock_threshold'
>;

export default function LowStockList({
    low_stock,
    low_stock_threshold,
}: LowStockListProps) {
    return (
        <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/3">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">
                Low stock
            </h2>
            <p className="mb-4 text-sm text-gray-500">
                {low_stock_threshold} or fewer left
            </p>

            {low_stock.length === 0 ? (
                <p className="text-sm text-gray-500">
                    Every variant has enough stock.
                </p>
            ) : (
                <ul className="divide-y divide-gray-200 dark:divide-gray-800">
                    {low_stock.map((variant) => (
                        <li
                            key={variant.variant_id}
                            className="flex items-center justify-between gap-3 py-3"
                        >
                            <div className="min-w-0">
                                <Link
                                    href={edit(variant.product_id).url}
                                    className="hover:text-brand-500 font-medium text-gray-900 dark:text-white/90"
                                >
                                    {variant.product_name}
                                </Link>
                                <p className="truncate text-xs text-gray-500">
                                    {variant.variant_label
                                        ? `${variant.variant_label} · `
                                        : ''}
                                    SKU {variant.sku}
                                </p>
                            </div>
                            <Badge
                                size="sm"
                                color={
                                    variant.stock === 0 ? 'error' : 'warning'
                                }
                            >
                                {variant.stock === 0
                                    ? 'Out of stock'
                                    : `${variant.stock} left`}
                            </Badge>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

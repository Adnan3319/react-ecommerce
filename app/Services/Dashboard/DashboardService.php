<?php

namespace App\Services\Dashboard;

use App\Enums\ActiveStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Numbers of the admin dashboard as one plain array, cached for 5 minutes (see DashboardCache).
 * "Today" and the day buckets follow the application timezone (Asia/Dhaka).
 */
class DashboardService
{
    private const TREND_DAYS = 30;

    private const RECENT_ORDERS = 5;

    private const LOW_STOCK_ROWS = 10;

    private const DAY_FORMAT = 'Y-m-d';

    /**
     * @return array{
     *     today: array{orders: int, revenue: string, pending: int},
     *     trend: list<array{date: string, orders: int}>,
     *     outcome: array{delivered: int, cancelled: int, delivered_percent: ?int},
     *     recent: list<array<string, mixed>>,
     *     low_stock: list<array<string, mixed>>,
     *     low_stock_threshold: int
     * }
     */
    public function summary(): array
    {
        return Cache::remember(DashboardCache::KEY, DashboardCache::TTL_SECONDS, fn (): array => $this->build());
    }

    /**
     * @return array<string, mixed>
     */
    private function build(): array
    {
        $threshold = (int) config('shop.low_stock_threshold');
        $from = now()->subDays(self::TREND_DAYS - 1)->startOfDay();

        return [
            'today' => $this->today(),
            'trend' => $this->trend($from),
            'outcome' => $this->outcome($from),
            'recent' => $this->recent(),
            'low_stock' => $this->lowStock($threshold),
            'low_stock_threshold' => $threshold,
        ];
    }

    /**
     * Revenue is what was delivered (and so paid) today, not what was ordered today. Pending counts
     * every order that is waiting right now, whenever it was placed.
     *
     * @return array{orders: int, revenue: string, pending: int}
     */
    private function today(): array
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();

        return [
            'orders' => Order::query()->whereBetween('created_at', [$start, $end])->count(),
            'revenue' => Money::format((int) Order::query()
                ->where('status', OrderStatus::Delivered)
                ->whereBetween('delivered_at', [$start, $end])
                ->sum('total')),
            'pending' => Order::query()->where('status', OrderStatus::Pending)->count(),
        ];
    }

    /**
     * Orders placed per day for the last 30 days, days without orders included.
     *
     * @return list<array{date: string, orders: int}>
     */
    private function trend(CarbonInterface $from): array
    {
        $perDay = Order::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, self::TREND_DAYS - 1))
            ->map(fn (int $offset): string => $from->copy()->addDays($offset)->format(self::DAY_FORMAT))
            ->map(fn (string $day): array => ['date' => $day, 'orders' => (int) ($perDay[$day] ?? 0)])
            ->all();
    }

    /**
     * Delivered against cancelled among the orders placed in the window (open orders are not counted).
     *
     * @return array{delivered: int, cancelled: int, delivered_percent: ?int}
     */
    private function outcome(CarbonInterface $from): array
    {
        $counts = Order::query()
            ->where('created_at', '>=', $from)
            ->whereIn('status', [OrderStatus::Delivered, OrderStatus::Cancelled])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $delivered = (int) ($counts[OrderStatus::Delivered->value] ?? 0);
        $cancelled = (int) ($counts[OrderStatus::Cancelled->value] ?? 0);
        $closed = $delivered + $cancelled;

        return [
            'delivered' => $delivered,
            'cancelled' => $cancelled,
            'delivered_percent' => $closed === 0 ? null : (int) round($delivered / $closed * 100),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recent(): array
    {
        return Order::query()
            ->select(['id', 'number', 'status', 'total', 'shipping_name', 'created_at'])
            ->latest('id')
            ->limit(self::RECENT_ORDERS)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'number' => $order->number,
                'customer_name' => $order->shipping_name,
                'total' => Money::format($order->total),
                'placed_at' => $order->created_at->toIso8601String(),
                'status' => ['value' => $order->status->value, 'label' => $order->status->label(), 'color' => $order->status->color()],
            ])
            ->all();
    }

    /**
     * Sellable variants that are running out, lowest stock first.
     *
     * @return list<array<string, mixed>>
     */
    private function lowStock(int $threshold): array
    {
        return ProductVariant::query()
            ->active()
            ->where('stock', '<=', $threshold)
            ->whereHas('product', fn (Builder $product) => $product->where('status', ActiveStatus::Active))
            ->with(['product:id,name', 'variantValues'])
            ->orderBy('stock')
            ->orderBy('id')
            ->limit(self::LOW_STOCK_ROWS)
            ->get()
            ->map(fn (ProductVariant $variant): array => [
                'variant_id' => $variant->id,
                'product_id' => $variant->product_id,
                'product_name' => $variant->product->name,
                'variant_label' => $variant->combination_key === ProductVariant::DEFAULT_COMBINATION ? null : $variant->label,
                'sku' => $variant->sku,
                'stock' => $variant->stock,
            ])
            ->all();
    }
}

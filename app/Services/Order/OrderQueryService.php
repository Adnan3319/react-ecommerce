<?php

namespace App\Services\Order;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Reads for the admin order screens.
 */
class OrderQueryService
{
    /**
     * @param  array{search: ?string, status: ?OrderStatus, source: ?OrderSource, from: ?CarbonInterface, per_page: int}  $filters
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Order::query()
            ->select(['id', 'customer_id', 'number', 'status', 'source', 'total', 'shipping_name', 'shipping_phone', 'delivery_zone_label', 'created_at'])
            ->status($filters['status'])
            ->search($filters['search'])
            ->placedBetween($filters['from'], null)
            ->when($filters['source'], fn ($query, OrderSource $source) => $query->where('orders.source', $source))
            ->withCount('items')
            ->latest('id')
            ->paginate($filters['per_page'])
            ->withQueryString();
    }

    /**
     * Number of orders per status (every status present, zero included) from one grouped query.
     *
     * @return Collection<string, int>
     */
    public function statusCounts(): Collection
    {
        $counts = Order::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $status) => [$status->value => (int) ($counts[$status->value] ?? 0)]);
    }

    /**
     * Relations the detail page shows, loaded in a fixed number of queries.
     */
    public function detail(Order $order): Order
    {
        return $order->load(['customer', 'items', 'statusHistories.changedBy:id,name']);
    }

    /**
     * Past orders of the customer behind this order, to spot fake or repeat-cancelling buyers.
     *
     * @return array{delivered: int, cancelled: int, pending: int}
     */
    public function customerHistory(Order $order): array
    {
        return [
            'delivered' => $order->customer->delivered_count,
            'cancelled' => $order->customer->cancelled_count,
            'pending' => Order::query()->where('customer_id', $order->customer_id)->where('status', OrderStatus::Pending)->count(),
        ];
    }
}

<?php

namespace App\Services\Order;

use App\Enums\ActiveStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Jobs\NotifyAdminOfNewOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\Product\StockService;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Places a Cash on Delivery order (doc/order/README.md §5). Prices, delivery charge and totals are
 * always taken from the database and config, never from the request.
 *
 * Failures are ValidationExceptions keyed for the order form: `phone`, `items`, `items.N.variant`,
 * `items.N.quantity`, `delivery_zone` and `order`.
 */
class CheckoutService
{
    private const PENDING_WINDOW_HOURS = 24;

    public function __construct(
        private readonly DeliveryZones $zones,
        private readonly OrderNumberGenerator $numbers,
        private readonly StockService $stock,
    ) {}

    /**
     * Returns the new order, or the existing one when this is a repeat of an order that was already placed
     * (same idempotency key, or same phone and items inside the duplicate window).
     *
     * @throws ValidationException
     */
    public function place(CheckoutData $data): Order
    {
        $existing = $this->findByKey($data->idempotencyKey);

        if ($existing) {
            return $existing;
        }

        $phone = $this->validateInput($data);

        try {
            ['order' => $order, 'created' => $created] = DB::transaction(fn () => $this->placeInTransaction($data, $phone));
        } catch (UniqueConstraintViolationException $e) {
            // Two identical requests raced: the other one won, hand back its order.
            $winner = str_contains($e->getMessage(), 'idempotency_key') ? $this->findByKey($data->idempotencyKey) : null;

            return $winner ?? throw $e;
        }

        if ($created) {
            NotifyAdminOfNewOrder::dispatch($order->id);
        }

        return $order;
    }

    private function findByKey(string $key): ?Order
    {
        return Order::query()->with('items')->where('idempotency_key', $key)->first();
    }

    /**
     * Cheap checks that need no lock. Returns the normalized phone.
     *
     * @throws ValidationException
     */
    private function validateInput(CheckoutData $data): string
    {
        $errors = [];
        $phone = Phone::normalize($data->phone);

        if ($phone === null) {
            $errors['phone'] = 'Enter a valid Bangladeshi mobile number (01XXXXXXXXX).';
        }

        if (! in_array($data->deliveryZone, $this->zones->keys(), true)) {
            $errors['delivery_zone'] = 'Choose a delivery area.';
        }

        if ($data->items === []) {
            $errors['items'] = 'Your order has no items.';
        }

        $max = (int) config('shop.max_qty_per_item');
        $seen = [];

        foreach ($data->items as $index => $item) {
            if (isset($seen[$item['variant_id']])) {
                $errors["items.{$index}.variant"] = 'This item is listed twice.';
            }

            $seen[$item['variant_id']] = true;

            if ($item['quantity'] < 1 || $item['quantity'] > $max) {
                $errors["items.{$index}.quantity"] = "Quantity must be between 1 and {$max}.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $phone;
    }

    /**
     * @return array{order: Order, created: bool}
     */
    private function placeInTransaction(CheckoutData $data, string $phone): array
    {
        $customer = $this->lockedCustomer($data, $phone);

        if ($customer->is_blocked) {
            throw ValidationException::withMessages(['phone' => 'We cannot take orders from this phone number.']);
        }

        $duplicate = $this->recentDuplicate($customer, $data->items);

        if ($duplicate) {
            return ['order' => $duplicate, 'created' => false];
        }

        $this->guardPendingLimit($customer);

        $variants = $this->lockVariants($data->items);

        $order = $this->createOrder($data, $customer, $phone, $variants);

        foreach ($data->items as $item) {
            $this->stock->deduct($variants[$item['variant_id']], $item['quantity'], StockMovementReason::OrderPlaced, $order);
        }

        $customer->increment('orders_count', 1, ['name' => trim($data->name)]);

        return ['order' => $order->load('items'), 'created' => true];
    }

    /**
     * The customer of this phone, created or restored when needed, with its row locked so that
     * concurrent orders of one phone run one after another.
     */
    private function lockedCustomer(CheckoutData $data, string $phone): Customer
    {
        $found = Customer::withTrashed()->createOrFirst(['phone' => $phone], ['name' => trim($data->name)]);
        $customer = Customer::withTrashed()->lockForUpdate()->findOrFail($found->id);

        if ($customer->trashed()) {
            $customer->restore();
        }

        return $customer;
    }

    /**
     * An order of this customer with exactly these lines inside the duplicate window.
     *
     * @param  list<array{variant_id: int, quantity: int}>  $items
     */
    private function recentDuplicate(Customer $customer, array $items): ?Order
    {
        $wanted = $this->signature(array_column($items, 'quantity', 'variant_id'));

        return Order::query()
            ->with('items')
            ->where('customer_id', $customer->id)
            ->where('created_at', '>=', now()->subMinutes((int) config('shop.duplicate_window_minutes')))
            ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Returned])
            ->latest('id')
            ->get()
            ->first(fn (Order $order) => $this->signature($order->items->pluck('quantity', 'product_variant_id')->all()) === $wanted);
    }

    /**
     * @param  array<int, int>  $quantities  quantity by variant id
     */
    private function signature(array $quantities): string
    {
        ksort($quantities);

        return json_encode($quantities, JSON_THROW_ON_ERROR);
    }

    private function guardPendingLimit(Customer $customer): void
    {
        $pending = Order::query()
            ->where('customer_id', $customer->id)
            ->where('status', OrderStatus::Pending)
            ->where('created_at', '>=', now()->subHours(self::PENDING_WINDOW_HOURS))
            ->count();

        if ($pending >= (int) config('shop.max_pending_per_phone')) {
            throw ValidationException::withMessages([
                'order' => 'You already have several orders waiting for confirmation. Please wait until they are confirmed.',
            ]);
        }
    }

    /**
     * Locks the ordered variants in id order (no deadlocks) and checks each line.
     *
     * @param  list<array{variant_id: int, quantity: int}>  $items
     * @return Collection<int, ProductVariant>
     *
     * @throws ValidationException
     */
    private function lockVariants(array $items): Collection
    {
        $variants = ProductVariant::query()
            ->whereIn('id', array_column($items, 'variant_id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->with(['product.mainImage', 'image', 'variantValues'])
            ->get()
            ->keyBy('id');

        $errors = [];

        foreach ($items as $index => $item) {
            $variant = $variants->get($item['variant_id']);

            if ($variant === null || $variant->status !== ActiveStatus::Active || $variant->product?->status !== ActiveStatus::Active) {
                $errors["items.{$index}.variant"] = 'This item is no longer available.';
            } elseif ($variant->stock < $item['quantity']) {
                $errors["items.{$index}.quantity"] = $variant->stock === 0 ? 'Out of stock.' : "Only {$variant->stock} left.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $variants;
    }

    /**
     * @param  Collection<int, ProductVariant>  $variants
     */
    private function createOrder(CheckoutData $data, Customer $customer, string $phone, Collection $variants): Order
    {
        $lines = collect($data->items)->map(fn (array $item) => $this->line($variants[$item['variant_id']], $item['quantity']));
        $subtotal = $lines->sum('line_total');
        $deliveryCharge = $this->zones->charge($data->deliveryZone);

        $order = $this->numbers->createWithNumber(fn (string $number) => Order::create([
            'number' => $number,
            'public_token' => $this->numbers->token(),
            'customer_id' => $customer->id,
            'status' => OrderStatus::Pending,
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Unpaid,
            'source' => $data->source,
            'subtotal' => $subtotal,
            'delivery_charge' => $deliveryCharge,
            'total' => $subtotal + $deliveryCharge,
            'shipping_name' => trim($data->name),
            'shipping_phone' => $phone,
            'shipping_address' => trim($data->address),
            'delivery_zone' => $data->deliveryZone,
            'delivery_zone_label' => $this->zones->get($data->deliveryZone)['label'],
            'customer_note' => $data->note,
            'idempotency_key' => $data->idempotencyKey,
            'utm_source' => $data->utmSource,
            'utm_medium' => $data->utmMedium,
            'utm_campaign' => $data->utmCampaign,
            'fbclid' => $data->fbclid,
            'ip_address' => $data->ipAddress,
            'user_agent' => $data->userAgent === null ? null : mb_substr($data->userAgent, 0, 255),
        ]));

        $order->items()->createMany($lines->all());
        $order->statusHistories()->create(['from_status' => null, 'to_status' => OrderStatus::Pending]);

        return $order;
    }

    /**
     * One order line as a snapshot of the variant right now.
     *
     * @return array<string, mixed>
     */
    private function line(ProductVariant $variant, int $quantity): array
    {
        $unitPrice = $variant->effective_price;

        return [
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'variant_label' => $variant->combination_key === ProductVariant::DEFAULT_COMBINATION ? null : $variant->label,
            'sku' => $variant->sku,
            'image_path' => $variant->image?->path ?? $variant->product->mainImage?->path,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => $unitPrice * $quantity,
        ];
    }
}

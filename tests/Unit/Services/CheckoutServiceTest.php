<?php

use App\Enums\ActiveStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Jobs\NotifyAdminOfNewOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\Order\CheckoutData;
use App\Services\Order\CheckoutService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Queue::fake();
    $this->checkout = app(CheckoutService::class);
});

function checkoutVariant(int $price = 100000, int $stock = 10): ProductVariant
{
    return Product::factory()->simple($price, $stock)->create()->variants()->sole();
}

/**
 * @param  array<int, int>  $quantities  quantity by variant id
 * @param  array<string, mixed>  $overrides  CheckoutData constructor arguments
 */
function checkoutData(array $quantities, array $overrides = []): CheckoutData
{
    $items = [];

    foreach ($quantities as $variantId => $quantity) {
        $items[] = ['variant_id' => $variantId, 'quantity' => $quantity];
    }

    return new CheckoutData(...[
        'items' => $items,
        'name' => 'Rahim Uddin',
        'phone' => '01712345678',
        'address' => 'House 1, Road 2, Dhanmondi, Dhaka',
        'deliveryZone' => 'inside',
        'idempotencyKey' => (string) Str::uuid(),
        'source' => OrderSource::Cart,
        ...$overrides,
    ]);
}

/**
 * @return array<string, array<int, string>>
 */
function checkoutErrors(Closure $place): array
{
    try {
        $place();
    } catch (ValidationException $e) {
        return $e->errors();
    }

    return [];
}

/** Stock movements written by orders, not by product setup. */
function checkoutOrderMovements(): int
{
    return StockMovement::where('reason', StockMovementReason::OrderPlaced)->count();
}

describe('placing an order', function () {
    it('totals two lines with the delivery charge, snapshots them and reduces stock', function () {
        $shirt = checkoutVariant(100000, 10);
        $belt = checkoutVariant(250000, 5);
        $data = checkoutData([$shirt->id => 2, $belt->id => 1], [
            'note' => 'Call before delivery',
            'utmSource' => 'facebook',
            'fbclid' => 'abc123',
            'ipAddress' => '203.0.113.9',
            'userAgent' => 'Pest',
        ]);

        $order = $this->checkout->place($data);

        expect($order->subtotal)->toBe(450000)
            ->and($order->delivery_charge)->toBe(7000)
            ->and($order->total)->toBe(457000)
            ->and($order->status)->toBe(OrderStatus::Pending)
            ->and($order->payment_status)->toBe(PaymentStatus::Unpaid)
            ->and($order->source)->toBe(OrderSource::Cart)
            ->and($order->number)->toMatch('/^CK-\d{6}-0001$/')
            ->and($order->public_token)->toHaveLength(32)
            ->and($order->shipping_phone)->toBe('01712345678')
            ->and($order->delivery_zone_label)->toBe('Inside City')
            ->and($order->customer_note)->toBe('Call before delivery')
            ->and($order->utm_source)->toBe('facebook')
            ->and($order->fbclid)->toBe('abc123')
            ->and($order->ip_address)->toBe('203.0.113.9');

        $lines = $order->items->sortBy('product_variant_id')->values();

        expect($lines)->toHaveCount(2)
            ->and($lines[0]->only(['product_variant_id', 'sku', 'unit_price', 'quantity', 'line_total']))
            ->toBe(['product_variant_id' => $shirt->id, 'sku' => $shirt->sku, 'unit_price' => 100000, 'quantity' => 2, 'line_total' => 200000])
            ->and($lines[0]->product_name)->toBe($shirt->product->name)
            ->and($lines[1]->line_total)->toBe(250000)
            ->and($shirt->fresh()->stock)->toBe(8)
            ->and($belt->fresh()->stock)->toBe(4);
    });

    it('writes an order_placed movement per line that points at the order', function () {
        $shirt = checkoutVariant(100000, 10);
        $belt = checkoutVariant(250000, 5);

        $order = $this->checkout->place(checkoutData([$shirt->id => 2, $belt->id => 1]));

        $movements = StockMovement::where('reason', StockMovementReason::OrderPlaced)->orderBy('product_variant_id')->get();

        expect($movements->pluck('quantity')->all())->toBe([-2, -1])
            ->and($movements->pluck('reference_id')->unique()->all())->toBe([$order->id])
            ->and($movements->first()->reference_type)->toBe($order->getMorphClass())
            ->and(StockMovement::where('product_variant_id', $shirt->id)->sum('quantity'))->toBe(8);
    });

    it('writes the placed history row and dispatches the admin notification', function () {
        $variant = checkoutVariant();

        $order = $this->checkout->place(checkoutData([$variant->id => 1]));

        $history = $order->statusHistories->sole();

        expect($history->from_status)->toBeNull()
            ->and($history->to_status)->toBe(OrderStatus::Pending)
            ->and($history->changed_by)->toBeNull();
        Queue::assertPushed(NotifyAdminOfNewOrder::class, fn (NotifyAdminOfNewOrder $job) => $job->orderId === $order->id);
    });

    it('creates the customer by normalized phone and counts the order', function () {
        $variant = checkoutVariant();

        $this->checkout->place(checkoutData([$variant->id => 1], ['phone' => '+880 1712-345678', 'name' => 'Rahim']));

        $customer = Customer::sole();

        expect($customer->phone)->toBe('01712345678')
            ->and($customer->name)->toBe('Rahim')
            ->and($customer->orders_count)->toBe(1);
    });

    it('reuses the customer of a returning phone and keeps the latest name', function () {
        $variant = checkoutVariant();
        $this->checkout->place(checkoutData([$variant->id => 1], ['name' => 'Rahim']));

        $this->checkout->place(checkoutData([$variant->id => 2], ['name' => 'Rahim Uddin', 'phone' => '8801712345678']));

        $customer = Customer::sole();

        expect($customer->orders_count)->toBe(2)->and($customer->name)->toBe('Rahim Uddin');
    });

    it('restores a soft-deleted customer', function () {
        $variant = checkoutVariant();
        Customer::factory()->create(['phone' => '01712345678'])->delete();

        $this->checkout->place(checkoutData([$variant->id => 1]));

        expect(Customer::sole()->trashed())->toBeFalse();
    });

    it('charges the delivery zone chosen', function () {
        $variant = checkoutVariant(100000, 10);

        $order = $this->checkout->place(checkoutData([$variant->id => 1], ['deliveryZone' => 'outside']));

        expect($order->delivery_charge)->toBe(13000)
            ->and($order->total)->toBe(113000)
            ->and($order->delivery_zone_label)->toBe('Outside City');
    });

    it('uses the sale price and snapshots the variant label and image', function () {
        $product = Product::factory()->withOptions(['Size' => ['M', 'L']], 200000, 10)->create();
        $image = ProductImage::factory()->for($product)->create(['is_main' => true]);
        $variant = $product->variants()->orderBy('sort_order')->first();
        $variant->update(['sale_price' => 150000]);

        $order = $this->checkout->place(checkoutData([$variant->id => 2]));

        $line = $order->items->sole();

        expect($line->unit_price)->toBe(150000)
            ->and($line->line_total)->toBe(300000)
            ->and($line->variant_label)->toBe('M')
            ->and($line->image_path)->toBe($image->path);
    });

    it('stores no variant label for a simple product', function () {
        $variant = checkoutVariant();

        expect($this->checkout->place(checkoutData([$variant->id => 1]))->items->sole()->variant_label)->toBeNull();
    });

    it('ignores any price the client could send because the data has none', function () {
        $variant = checkoutVariant(100000, 10);
        $data = checkoutData([$variant->id => 1]);
        $variant->update(['price' => 120000]);

        $order = $this->checkout->place($data);

        expect($order->items->sole()->unit_price)->toBe(120000)
            ->and($order->subtotal)->toBe(120000)
            ->and(property_exists($data, 'price'))->toBeFalse();
    });
});

describe('rejecting an order', function () {
    it('names the line when the quantity is above the stock and writes nothing', function () {
        $enough = checkoutVariant(100000, 10);
        $short = checkoutVariant(100000, 2);

        $errors = checkoutErrors(fn () => $this->checkout->place(checkoutData([$enough->id => 1, $short->id => 3])));

        expect($errors)->toBe(['items.1.quantity' => ['Only 2 left.']])
            ->and(Order::count())->toBe(0)
            ->and(Customer::count())->toBe(0)
            ->and($enough->fresh()->stock)->toBe(10)
            ->and(checkoutOrderMovements())->toBe(0);
        Queue::assertNothingPushed();
    });

    it('says out of stock for a variant with no units', function () {
        $variant = checkoutVariant(100000, 0);

        $errors = checkoutErrors(fn () => $this->checkout->place(checkoutData([$variant->id => 1])));

        expect($errors)->toBe(['items.0.quantity' => ['Out of stock.']]);
    });

    it('can still sell the last unit', function () {
        $variant = checkoutVariant(100000, 1);

        $this->checkout->place(checkoutData([$variant->id => 1]));

        expect($variant->fresh()->stock)->toBe(0);
    });

    it('rejects a quantity above max_qty_per_item', function () {
        config(['shop.max_qty_per_item' => 3]);
        $variant = checkoutVariant(100000, 50);

        $errors = checkoutErrors(fn () => $this->checkout->place(checkoutData([$variant->id => 4])));

        expect($errors)->toBe(['items.0.quantity' => ['Quantity must be between 1 and 3.']])
            ->and(Order::count())->toBe(0);
    });

    it('rejects a zero quantity', function () {
        $variant = checkoutVariant();

        expect(checkoutErrors(fn () => $this->checkout->place(checkoutData([$variant->id => 0]))))->toHaveKey('items.0.quantity');
    });

    it('rejects an unavailable variant on its line', function (Closure $makeUnavailable) {
        $variant = checkoutVariant();
        $makeUnavailable($variant);

        $errors = checkoutErrors(fn () => $this->checkout->place(checkoutData([$variant->id => 1])));

        expect($errors)->toHaveKey('items.0.variant')
            ->and(Order::count())->toBe(0);
    })->with([
        'inactive variant' => [fn (ProductVariant $variant) => $variant->update(['status' => ActiveStatus::Inactive])],
        'inactive product' => [fn (ProductVariant $variant) => $variant->product->update(['status' => ActiveStatus::Inactive])],
        'soft-deleted variant' => [fn (ProductVariant $variant) => $variant->delete()],
    ]);

    it('rejects a variant that does not exist', function () {
        expect(checkoutErrors(fn () => $this->checkout->place(checkoutData([999999 => 1]))))->toHaveKey('items.0.variant');
    });

    it('rejects the same variant listed twice', function () {
        $variant = checkoutVariant();
        $data = checkoutData([], ['items' => [
            ['variant_id' => $variant->id, 'quantity' => 1],
            ['variant_id' => $variant->id, 'quantity' => 2],
        ]]);

        expect(checkoutErrors(fn () => $this->checkout->place($data)))->toHaveKey('items.1.variant');
    });

    it('rejects an empty order', function () {
        expect(checkoutErrors(fn () => $this->checkout->place(checkoutData([]))))->toHaveKey('items');
    });

    it('rejects an invalid phone', function (string $phone) {
        $variant = checkoutVariant();

        $errors = checkoutErrors(fn () => $this->checkout->place(checkoutData([$variant->id => 1], ['phone' => $phone])));

        expect($errors)->toHaveKey('phone');
    })->with(['12345', 'not a phone', '01212345678']);

    it('rejects an unknown delivery zone', function () {
        $variant = checkoutVariant();

        $errors = checkoutErrors(fn () => $this->checkout->place(checkoutData([$variant->id => 1], ['deliveryZone' => 'mars'])));

        expect($errors)->toHaveKey('delivery_zone');
    });

    it('rejects a blocked phone', function () {
        $variant = checkoutVariant();
        Customer::factory()->blocked()->create(['phone' => '01712345678']);

        $errors = checkoutErrors(fn () => $this->checkout->place(checkoutData([$variant->id => 1])));

        expect($errors)->toHaveKey('phone')
            ->and(Order::count())->toBe(0)
            ->and($variant->fresh()->stock)->toBe(10);
    });
});

describe('repeated requests', function () {
    it('returns the same order for the same idempotency key', function () {
        $variant = checkoutVariant();
        $data = checkoutData([$variant->id => 1]);

        $first = $this->checkout->place($data);
        $second = $this->checkout->place($data);

        expect($second->id)->toBe($first->id)
            ->and(Order::count())->toBe(1)
            ->and($variant->fresh()->stock)->toBe(9);
        Queue::assertPushed(NotifyAdminOfNewOrder::class, 1);
    });

    it('returns the existing order for the same phone and items inside the duplicate window', function () {
        $variant = checkoutVariant();
        $first = $this->checkout->place(checkoutData([$variant->id => 2]));

        $second = $this->checkout->place(checkoutData([$variant->id => 2]));

        expect($second->id)->toBe($first->id)
            ->and(Order::count())->toBe(1)
            ->and($variant->fresh()->stock)->toBe(8);
        Queue::assertPushed(NotifyAdminOfNewOrder::class, 1);
    });

    it('places a new order once the duplicate window has passed', function () {
        $variant = checkoutVariant();
        $this->checkout->place(checkoutData([$variant->id => 2]));
        $this->travel(11)->minutes();

        $this->checkout->place(checkoutData([$variant->id => 2]));

        expect(Order::count())->toBe(2)->and($variant->fresh()->stock)->toBe(6);
    });

    it('treats different quantities or items as a new order', function () {
        $first = checkoutVariant();
        $second = checkoutVariant();
        $this->checkout->place(checkoutData([$first->id => 2]));

        $this->checkout->place(checkoutData([$first->id => 3]));
        $this->checkout->place(checkoutData([$first->id => 2, $second->id => 1]));

        expect(Order::count())->toBe(3);
    });

    it('does not treat a cancelled order as a duplicate', function () {
        $variant = checkoutVariant();
        $customer = Customer::factory()->create(['phone' => '01712345678']);
        $cancelled = Order::factory()->cancelled()->for($customer)->create();
        $cancelled->items()->create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => 'x', 'sku' => 'x', 'unit_price' => 1, 'quantity' => 1, 'line_total' => 1,
        ]);

        $order = $this->checkout->place(checkoutData([$variant->id => 1]));

        expect($order->id)->not->toBe($cancelled->id);
    });
});

describe('pending order limit', function () {
    it('rejects the 4th pending order of a phone within 24 hours', function () {
        $variant = checkoutVariant(100000, 50);
        $customer = Customer::factory()->create(['phone' => '01712345678']);
        Order::factory()->count(3)->for($customer)->create();

        $errors = checkoutErrors(fn () => $this->checkout->place(checkoutData([$variant->id => 1])));

        expect($errors)->toHaveKey('order')
            ->and(Order::count())->toBe(3)
            ->and($variant->fresh()->stock)->toBe(50);
    });

    it('accepts the 3rd pending order', function () {
        $variant = checkoutVariant(100000, 50);
        $customer = Customer::factory()->create(['phone' => '01712345678']);
        Order::factory()->count(2)->for($customer)->create();

        $this->checkout->place(checkoutData([$variant->id => 1]));

        expect(Order::count())->toBe(3);
    });

    it('ignores pending orders older than 24 hours and orders that are no longer pending', function () {
        $variant = checkoutVariant(100000, 50);
        $customer = Customer::factory()->create(['phone' => '01712345678']);
        Order::factory()->count(2)->for($customer)->create(['created_at' => now()->subHours(25)]);
        Order::factory()->confirmed()->for($customer)->create();
        Order::factory()->cancelled()->for($customer)->create();

        $this->checkout->place(checkoutData([$variant->id => 1]));

        expect(Order::count())->toBe(5);
    });

    it('does not count the pending orders of another phone', function () {
        $variant = checkoutVariant(100000, 50);
        Order::factory()->count(3)->for(Customer::factory()->create())->create();

        $this->checkout->place(checkoutData([$variant->id => 1]));

        expect(Order::count())->toBe(4);
    });
});

describe('the last unit', function () {
    it('goes to the first order only', function () {
        $variant = checkoutVariant(100000, 1);
        $first = checkoutData([$variant->id => 1], ['phone' => '01711111111']);
        $second = checkoutData([$variant->id => 1], ['phone' => '01722222222']);

        $order = $this->checkout->place($first);
        $errors = checkoutErrors(fn () => $this->checkout->place($second));

        expect($order->exists)->toBeTrue()
            ->and($errors)->toBe(['items.0.quantity' => ['Out of stock.']])
            ->and(Order::count())->toBe(1)
            ->and($variant->fresh()->stock)->toBe(0)
            ->and(StockMovement::where('product_variant_id', $variant->id)->sum('quantity'))->toBe(0);
    });
});

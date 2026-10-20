<?php

use App\Enums\StockMovementReason;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Dashboard\DashboardCache;
use App\Services\Product\StockService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

const DASHBOARD_NOW = '2026-10-20 15:00:00';

beforeEach(function () {
    $this->travelTo(Carbon::parse(DASHBOARD_NOW, 'Asia/Dhaka'));
});

/**
 * Props of the dashboard page.
 *
 * @return array<string, mixed>
 */
function dashboardProps(): array
{
    return test()->get(route('admin.dashboard'))->assertOk()->viewData('page')['props'];
}

function dashboardVariant(int $stock, array $product = [], array $variant = []): ProductVariant
{
    $created = Product::factory()->simple(100000, $stock)->create($product)->variants()->sole();
    $created->update($variant);

    return $created;
}

describe('access', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    });

    it('renders the dashboard for an admin', function () {
        actingAsAdmin();

        $this->get(route('admin.dashboard'))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard/Ecommerce', false)
            ->has('today')->has('trend', 30)->has('outcome')->has('recent')->has('low_stock'));
    });
});

describe('today', function () {
    beforeEach(fn () => actingAsAdmin());

    it('counts orders placed today, revenue delivered today and every pending order', function () {
        Order::factory()->create(['total' => 100000]);
        Order::factory()->create(['total' => 100000, 'created_at' => now()->subDay()]);
        Order::factory()->delivered()->create(['total' => 250000, 'delivered_at' => now()]);
        Order::factory()->delivered()->create(['total' => 80000, 'created_at' => now()->subDays(3), 'delivered_at' => now()]);
        Order::factory()->delivered()->create(['total' => 999900, 'delivered_at' => now()->subDay()]);
        Order::factory()->cancelled()->create(['total' => 777700]);

        expect(dashboardProps()['today'])->toBe([
            'orders' => 4,
            'revenue' => '৳3,300',
            'pending' => 2,
        ]);
    });

    it('is all zeros without orders', function () {
        expect(dashboardProps()['today'])->toBe(['orders' => 0, 'revenue' => '৳0', 'pending' => 0]);
    });
});

describe('last 30 days', function () {
    beforeEach(fn () => actingAsAdmin());

    it('has one bucket per day, zeros included, ending today', function () {
        Order::factory()->count(2)->create();
        Order::factory()->create(['created_at' => now()->subDays(2)]);
        Order::factory()->create(['created_at' => now()->subDays(29)]);
        Order::factory()->create(['created_at' => now()->subDays(30)]);

        $trend = collect(dashboardProps()['trend']);

        expect($trend)->toHaveCount(30)
            ->and($trend->first())->toBe(['date' => '2026-09-21', 'orders' => 1])
            ->and($trend->last())->toBe(['date' => '2026-10-20', 'orders' => 2])
            ->and($trend->firstWhere('date', '2026-10-18')['orders'])->toBe(1)
            ->and($trend->firstWhere('date', '2026-10-19')['orders'])->toBe(0)
            ->and($trend->sum('orders'))->toBe(4);
    });

    it('compares delivered with cancelled orders placed in the window', function () {
        Order::factory()->count(3)->delivered()->create(['created_at' => now()->subDays(5)]);
        Order::factory()->cancelled()->create(['created_at' => now()->subDays(10)]);
        Order::factory()->create();
        Order::factory()->shipped()->create();
        Order::factory()->count(5)->cancelled()->create(['created_at' => now()->subDays(45)]);

        expect(dashboardProps()['outcome'])->toBe(['delivered' => 3, 'cancelled' => 1, 'delivered_percent' => 75]);
    });

    it('has no percentage while no order is closed', function () {
        Order::factory()->create();

        expect(dashboardProps()['outcome'])->toBe(['delivered' => 0, 'cancelled' => 0, 'delivered_percent' => null]);
    });
});

describe('recent orders', function () {
    it('lists the 5 newest orders first with the status badge', function () {
        actingAsAdmin();
        foreach (range(1, 7) as $n) {
            Order::factory()->create(['number' => "CK-{$n}", 'total' => $n * 10000, 'shipping_name' => "Buyer {$n}"]);
        }
        Order::query()->where('number', 'CK-7')->update(['status' => 'confirmed']);

        $recent = dashboardProps()['recent'];

        expect(array_column($recent, 'number'))->toBe(['CK-7', 'CK-6', 'CK-5', 'CK-4', 'CK-3'])
            ->and($recent[0])->toMatchArray([
                'customer_name' => 'Buyer 7',
                'total' => '৳700',
                'status' => ['value' => 'confirmed', 'label' => 'Confirmed', 'color' => 'info'],
            ]);
    });
});

describe('low stock', function () {
    beforeEach(fn () => actingAsAdmin());

    it('lists sellable variants at or below the threshold, lowest first', function () {
        config(['shop.low_stock_threshold' => 5]);
        $atLimit = dashboardVariant(5, ['name' => 'At limit']);
        $empty = dashboardVariant(0, ['name' => 'Empty']);
        dashboardVariant(6, ['name' => 'Plenty']);
        dashboardVariant(1, ['name' => 'Inactive product'], []);
        Product::query()->where('name', 'Inactive product')->update(['status' => 'inactive']);
        dashboardVariant(2, ['name' => 'Inactive variant'], ['status' => 'inactive']);
        dashboardVariant(3, ['name' => 'Deleted'])->product->delete();

        $rows = dashboardProps()['low_stock'];

        expect(array_column($rows, 'product_name'))->toBe(['Empty', 'At limit'])
            ->and($rows[0])->toMatchArray(['variant_id' => $empty->id, 'product_id' => $empty->product_id, 'stock' => 0, 'variant_label' => null])
            ->and($rows[1]['stock'])->toBe($atLimit->stock)
            ->and(dashboardProps()['low_stock_threshold'])->toBe(5);
    });

    it('shows at most 10 rows', function () {
        foreach (range(1, 12) as $ignored) {
            dashboardVariant(1);
        }

        expect(dashboardProps()['low_stock'])->toHaveCount(10);
    });
});

describe('cache', function () {
    beforeEach(fn () => actingAsAdmin());

    it('serves the numbers from the cache instead of counting again', function () {
        Order::factory()->create();
        expect(dashboardProps()['today']['orders'])->toBe(1);

        Order::factory()->create();
        expect(dashboardProps()['today']['orders'])->toBe(1)
            ->and(Cache::has(DashboardCache::KEY))->toBeTrue();

        Cache::forget(DashboardCache::KEY);
        expect(dashboardProps()['today']['orders'])->toBe(2);
    });

    it('is cleared by an order status change', function () {
        $order = Order::factory()->create();
        expect(dashboardProps()['today']['pending'])->toBe(1);

        $this->post(route('admin.orders.transition', $order), ['to' => 'confirmed'])->assertRedirect();

        expect(dashboardProps()['today']['pending'])->toBe(0);
    });

    it('is cleared when an order is placed', function () {
        Queue::fake();
        $variant = dashboardVariant(10);
        expect(dashboardProps()['today']['orders'])->toBe(0);

        $this->post(route('orders.store'), [
            'name' => 'Nusrat Jahan',
            'phone' => '01712345678',
            'address' => 'House 1, Road 2, Dhanmondi, Dhaka',
            'delivery_zone' => 'inside_dhaka',
            'items' => [['variant_id' => $variant->id, 'quantity' => 1]],
            'idempotency_key' => (string) Str::uuid(),
            'website' => '',
        ])->assertSessionHasNoErrors();

        expect(dashboardProps()['today']['orders'])->toBe(1);
    });

    it('is cleared by a stock change', function () {
        config(['shop.low_stock_threshold' => 5]);
        $variant = dashboardVariant(5);
        $order = Order::factory()->create();
        expect(dashboardProps()['low_stock'])->toHaveCount(1);

        app(StockService::class)->restore($variant, 1, StockMovementReason::OrderCancelled, $order);

        expect(dashboardProps()['low_stock'])->toBe([]);
    });
});

describe('timezone', function () {
    it('numbers and dates an order placed at 00:30 Dhaka time with the new day', function () {
        actingAsAdmin();
        Queue::fake();
        // 00:30 on the 21st in Dhaka is still the 20th in UTC.
        $this->travelTo(Carbon::parse('2026-10-21 00:30:00', 'Asia/Dhaka'));
        $variant = dashboardVariant(10);

        $this->post(route('orders.store'), [
            'name' => 'Nusrat Jahan',
            'phone' => '01712345678',
            'address' => 'House 1, Road 2, Dhanmondi, Dhaka',
            'delivery_zone' => 'inside_dhaka',
            'items' => [['variant_id' => $variant->id, 'quantity' => 1]],
            'idempotency_key' => (string) Str::uuid(),
            'website' => '',
        ])->assertSessionHasNoErrors();

        $props = dashboardProps();
        expect(Order::query()->sole()->number)->toBe('CK-261021-0001')
            ->and($props['today']['orders'])->toBe(1)
            ->and(collect($props['trend'])->last())->toBe(['date' => '2026-10-21', 'orders' => 1])
            ->and(collect($props['trend'])->firstWhere('date', '2026-10-20')['orders'])->toBe(0);
    });

    it('runs the application in Asia/Dhaka', function () {
        expect(config('app.timezone'))->toBe('Asia/Dhaka')
            ->and(now()->getTimezone()->getName())->toBe('Asia/Dhaka');
    });
});

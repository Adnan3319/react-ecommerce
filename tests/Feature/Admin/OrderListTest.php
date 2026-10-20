<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Order\PendingOrderCount;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Order numbers on the list page for the given query string.
 *
 * @return array<int, string>
 */
function listedOrderNumbers(array $query = []): array
{
    $page = test()->get(route('admin.orders.index', $query))->assertOk()->viewData('page');

    return collect($page['props']['orders']['data'])->pluck('number')->all();
}

describe('access', function () {
    it('redirects guests to the login page', function () {
        $order = Order::factory()->create();

        $this->get(route('admin.orders.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.orders.show', $order))->assertRedirect(route('admin.login'));
        $this->post(route('admin.orders.transition', $order), ['to' => 'confirmed'])->assertRedirect(route('admin.login'));
    });

    it('shows the list to an active admin', function () {
        actingAsAdmin();
        Order::factory()->count(2)->create();

        $this->get(route('admin.orders.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Orders/Index', false)->has('orders.data', 2));
    });
});

describe('status tabs', function () {
    it('counts orders per status, zero included', function () {
        actingAsAdmin();
        Order::factory()->count(2)->create();
        Order::factory()->confirmed()->create();
        Order::factory()->cancelled()->create();

        $this->get(route('admin.orders.index'))->assertInertia(fn (Assert $page) => $page
            ->has('statuses', count(OrderStatus::cases()))
            ->where('statuses.0', ['value' => 'pending', 'label' => 'Pending', 'color' => 'warning', 'count' => 2])
            ->where('statuses.1.count', 1)
            ->where('statuses.2.count', 0)
            ->where('statuses.5.count', 1));
    });

    it('lists only the orders of the chosen status', function () {
        actingAsAdmin();
        Order::factory()->create(['number' => 'CK-1']);
        Order::factory()->shipped()->create(['number' => 'CK-2']);

        expect(listedOrderNumbers(['order_status' => 'shipped']))->toBe(['CK-2']);
    });

    it('rejects an unknown status', function () {
        actingAsAdmin();

        $this->get(route('admin.orders.index', ['order_status' => 'lost']))->assertSessionHasErrors('order_status');
    });
});

describe('filters', function () {
    beforeEach(fn () => actingAsAdmin());

    it('searches by number and by name', function () {
        Order::factory()->create(['number' => 'CK-251020-0042', 'shipping_name' => 'Nusrat Jahan']);
        Order::factory()->create(['number' => 'CK-251020-0043', 'shipping_name' => 'Rafiq Ahmed']);

        expect(listedOrderNumbers(['search' => '0042']))->toBe(['CK-251020-0042'])
            ->and(listedOrderNumbers(['search' => 'rafiq']))->toBe(['CK-251020-0043']);
    });

    it('treats LIKE wildcards in the search term literally', function () {
        Order::factory()->create(['number' => 'CK-1', 'shipping_name' => '100% Real']);
        Order::factory()->create(['number' => 'CK-2']);

        expect(listedOrderNumbers(['search' => '%']))->toBe(['CK-1']);
    });

    it('finds a phone however it is typed', function (string $term) {
        Order::factory()->create(['number' => 'CK-1', 'shipping_phone' => '01712345678']);
        Order::factory()->create(['number' => 'CK-2', 'shipping_phone' => '01819222333']);

        expect(listedOrderNumbers(['search' => $term]))->toBe(['CK-1']);
    })->with([
        'local' => '01712345678',
        'with country code' => '8801712345678',
        'with plus' => '+8801712345678',
        'with spaces' => '+880 1712 345678',
        'with dashes' => '01712-345-678',
        'partial with country code' => '+880 1712',
    ]);

    it('filters by source', function () {
        Order::factory()->buyNow()->create(['number' => 'CK-1']);
        Order::factory()->cart()->create(['number' => 'CK-2']);

        expect(listedOrderNumbers(['source' => 'buy_now']))->toBe(['CK-1']);
    });

    it('filters by date range preset', function () {
        $this->freezeTime();
        Order::factory()->create(['number' => 'CK-OLD', 'created_at' => now()->subDays(10)]);
        Order::factory()->create(['number' => 'CK-WEEK', 'created_at' => now()->subDays(5)]);
        Order::factory()->create(['number' => 'CK-TODAY']);

        expect(listedOrderNumbers(['range' => 'today']))->toBe(['CK-TODAY'])
            ->and(listedOrderNumbers(['range' => '7d']))->toBe(['CK-TODAY', 'CK-WEEK'])
            ->and(listedOrderNumbers(['range' => '30d']))->toBe(['CK-TODAY', 'CK-WEEK', 'CK-OLD']);
    });

    it('shows the item count of each order', function () {
        Order::factory()->withItems(3)->create();

        $this->get(route('admin.orders.index'))->assertInertia(fn (Assert $page) => $page->where('orders.data.0.items_count', 3));
    });
});

describe('pending badge', function () {
    it('shares the pending order count with admin pages', function () {
        actingAsAdmin();
        Order::factory()->count(2)->create();
        Order::factory()->confirmed()->create();

        $this->get(route('admin.orders.index'))->assertInertia(fn (Assert $page) => $page->where('admin.pendingOrders', 2));
    });

    it('is not shared with guests', function () {
        Order::factory()->create();

        $this->get(route('admin.login'))->assertInertia(fn (Assert $page) => $page->where('admin', null));
    });

    it('is cached until an order is placed or moved', function () {
        actingAsAdmin();
        $order = Order::factory()->create();
        expect(app(PendingOrderCount::class)->get())->toBe(1);

        Order::factory()->create();
        expect(app(PendingOrderCount::class)->get())->toBe(1);

        $this->post(route('admin.orders.transition', $order), ['to' => 'confirmed'])->assertRedirect();
        expect(app(PendingOrderCount::class)->get())->toBe(1);
    });
});

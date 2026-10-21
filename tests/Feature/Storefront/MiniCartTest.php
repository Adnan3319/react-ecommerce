<?php

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

function miniCartVisitor(): string
{
    $token = Str::random(40);
    test()->withCookie('cart_token', $token);

    return $token;
}

function miniCartLine(string $token, int $price = 150000, int $quantity = 2): void
{
    $variant = Product::factory()->simple($price, 10)->create()->variants()->sole();

    CartItem::query()->create([
        'cart_token' => $token,
        'product_variant_id' => $variant->id,
        'quantity' => $quantity,
        'price_at_add' => $price,
    ]);
}

/**
 * The `miniCart` prop as a partial reload of the Home page returns it.
 *
 * @return array{lines: list<array<string, mixed>>, subtotal: int, maxQty: int}
 */
function miniCartProp(): array
{
    $version = test()->get(route('home'))->viewData('page')['version'];

    return test()->get(route('home'), [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $version,
        'X-Inertia-Partial-Component' => 'Home',
        'X-Inertia-Partial-Data' => 'miniCart',
    ])->assertOk()->json('props.miniCart');
}

it('does not build the mini cart on a normal page load', function () {
    miniCartLine(miniCartVisitor());

    $this->get(route('home'))->assertInertia(fn (AssertableInertia $page) => $page->missing('miniCart'));
});

it('sends the lines, subtotal and quantity limit on a partial reload', function () {
    miniCartLine(miniCartVisitor(), price: 150000, quantity: 2);

    $mini = miniCartProp();

    expect($mini['subtotal'])->toBe(300000)
        ->and($mini['maxQty'])->toBe((int) config('shop.max_qty_per_item'))
        ->and($mini['lines'])->toHaveCount(1)
        ->and($mini['lines'][0]['quantity'])->toBe(2)
        ->and($mini['lines'][0]['line_total'])->toBe(300000);
});

it('sends an empty mini cart to a visitor without a cart', function () {
    $mini = miniCartProp();

    expect($mini['subtotal'])->toBe(0)
        ->and($mini['lines'])->toBe([]);
});

it('reflects a quantity change and a removal in the next partial reload', function () {
    $token = miniCartVisitor();
    miniCartLine($token, price: 100000, quantity: 1);
    $variantId = CartItem::query()->where('cart_token', $token)->value('product_variant_id');

    $this->patch(route('cart.items.update', $variantId), ['quantity' => 3])->assertRedirect();
    expect(miniCartProp()['subtotal'])->toBe(300000);

    $this->delete(route('cart.items.destroy', $variantId))->assertRedirect();
    expect(miniCartProp()['lines'])->toBe([]);
});

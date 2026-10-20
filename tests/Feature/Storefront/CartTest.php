<?php

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Makes the following requests of this test come from the visitor who owns `$token`.
 */
function cartVisitor(?string $token = null): string
{
    $token ??= Str::random(40);
    test()->withCookie('cart_token', $token);

    return $token;
}

function cartVariant(int $price = 100000, int $stock = 10): ProductVariant
{
    return Product::factory()->simple($price, $stock)->create()->variants()->sole();
}

function cartSeed(string $token, ProductVariant $variant, int $quantity = 1, ?int $priceAtAdd = null): CartItem
{
    return CartItem::query()->create([
        'cart_token' => $token,
        'product_variant_id' => $variant->id,
        'quantity' => $quantity,
        'price_at_add' => $priceAtAdd ?? $variant->effective_price,
    ]);
}

function cartTokenIn(TestResponse $response): string
{
    $cookie = $response->getCookie('cart_token', false);

    return CookieValuePrefix::remove(app('encrypter')->decrypt($cookie->getValue(), false));
}

/**
 * Props of the cart page.
 *
 * @return array<string, mixed>
 */
function cartPage(): array
{
    return test()->get(route('cart'))->assertOk()->viewData('page')['props'];
}

describe('cookie', function () {
    it('is issued on the first add as an encrypted httpOnly SameSite=Lax cookie', function () {
        $variant = cartVariant();

        $response = $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 1])->assertRedirect();

        $cookie = $response->getCookie('cart_token', false);
        expect($cookie->isHttpOnly())->toBeTrue()
            ->and($cookie->getSameSite())->toBe('lax')
            ->and($cookie->getExpiresTime())->toBeGreaterThan(now()->addDays(29)->getTimestamp())
            ->and($cookie->getValue())->not->toBe(cartTokenIn($response))
            ->and(CartItem::query()->where('cart_token', cartTokenIn($response))->count())->toBe(1);
    });

    it('is not issued to a visitor who never adds anything', function () {
        $this->get(route('home'))->assertCookieMissing('cart_token');
        $this->get(route('cart'))->assertCookieMissing('cart_token');
    });

    it('ignores a malformed token', function () {
        cartVisitor('short');

        expect(cartPage()['lines'])->toBe([]);
    });
});

describe('count', function () {
    it('is the total quantity in the cart, shared with every storefront page', function () {
        $token = cartVisitor();
        cartSeed($token, cartVariant(), 2);
        cartSeed($token, cartVariant(), 3);
        cartSeed(Str::random(40), cartVariant(), 9);

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('cart.count', 5));
    });

    it('is 0 without a query when the visitor has no cookie', function () {
        DB::enableQueryLog();

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('cart.count', 0));

        $cartQueries = collect(DB::getQueryLog())->filter(fn (array $query) => str_contains($query['query'], 'cart_items'));
        expect($cartQueries)->toBeEmpty();
    });

    it('reads the table once per request', function () {
        cartSeed(cartVisitor(), cartVariant(), 2);
        DB::enableQueryLog();

        $this->get(route('home'));

        $cartQueries = collect(DB::getQueryLog())->filter(fn (array $query) => str_contains($query['query'], 'cart_items'));
        expect($cartQueries)->toHaveCount(1);
    });
});

describe('add', function () {
    it('adds a line with the current price', function () {
        $variant = cartVariant(price: 165000);
        $token = cartVisitor();

        $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $item = CartItem::query()->where('cart_token', $token)->sole();
        expect($item->quantity)->toBe(2)
            ->and($item->price_at_add)->toBe(165000);
    });

    it('adds to the quantity of a line that is already there', function () {
        $variant = cartVariant();
        $token = cartVisitor();
        cartSeed($token, $variant, 2);

        $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 3])->assertSessionHasNoErrors();

        expect(CartItem::query()->where('cart_token', $token)->sole()->quantity)->toBe(5);
    });

    it('keeps the carts of two visitors apart', function () {
        $variant = cartVariant();
        cartSeed(Str::random(40), $variant, 4);
        $token = cartVisitor();

        $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 1]);

        expect(CartItem::query()->where('cart_token', $token)->sole()->quantity)->toBe(1)
            ->and(CartItem::query()->count())->toBe(2);
    });

    it('refuses what it should', function (callable $arrange, string $field) {
        $token = cartVisitor();
        $variantId = $arrange($token);

        $this->post(route('cart.items.store'), ['variant_id' => $variantId, 'quantity' => 3])->assertSessionHasErrors($field);

        expect(CartItem::query()->where('cart_token', $token)->count())->toBe(0);
    })->with([
        'unknown variant' => [fn () => 999999, 'variant'],
        'inactive variant' => [fn () => tap(cartVariant(), fn (ProductVariant $variant) => $variant->update(['status' => 'inactive']))->id, 'variant'],
        'inactive product' => [fn () => Product::factory()->inactive()->simple()->create()->variants()->sole()->id, 'variant'],
        'more than the stock' => [fn () => cartVariant(stock: 2)->id, 'quantity'],
        'sold out' => [fn () => cartVariant(stock: 0)->id, 'quantity'],
    ]);

    it('refuses more than the per-item limit, also across adds', function () {
        config(['shop.max_qty_per_item' => 4]);
        $variant = cartVariant(stock: 50);
        $token = cartVisitor();
        cartSeed($token, $variant, 3);

        $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 2])->assertSessionHasErrors('quantity');

        expect(CartItem::query()->where('cart_token', $token)->sole()->quantity)->toBe(3);
    });

    it('refuses a new line when the cart is full', function () {
        config(['shop.max_cart_lines' => 2]);
        $token = cartVisitor();
        cartSeed($token, cartVariant());
        cartSeed($token, cartVariant());

        $this->post(route('cart.items.store'), ['variant_id' => cartVariant()->id, 'quantity' => 1])->assertSessionHasErrors('cart');

        expect(CartItem::query()->where('cart_token', $token)->count())->toBe(2);
    });

    it('still lets a full cart change a line it already has', function () {
        config(['shop.max_cart_lines' => 1]);
        $variant = cartVariant();
        $token = cartVisitor();
        cartSeed($token, $variant);

        $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 1])->assertSessionHasNoErrors();

        expect(CartItem::query()->where('cart_token', $token)->sole()->quantity)->toBe(2);
    });

    it('validates the input', function (array $payload, string $field) {
        $this->post(route('cart.items.store'), $payload)->assertSessionHasErrors($field);
    })->with([
        'missing variant' => [['quantity' => 1], 'variant_id'],
        'zero quantity' => [['variant_id' => 1, 'quantity' => 0], 'quantity'],
        'text quantity' => [['variant_id' => 1, 'quantity' => 'many'], 'quantity'],
    ]);
});

describe('update and remove', function () {
    it('sets the quantity and refreshes the stored price', function () {
        $variant = cartVariant(price: 70000);
        $token = cartVisitor();
        cartSeed($token, $variant, 1, priceAtAdd: 90000);

        $this->patch(route('cart.items.update', $variant->id), ['quantity' => 4])->assertSessionHasNoErrors();

        $item = CartItem::query()->where('cart_token', $token)->sole();
        expect($item->quantity)->toBe(4)
            ->and($item->price_at_add)->toBe(70000);
    });

    it('refuses a quantity above the stock', function () {
        $variant = cartVariant(stock: 3);
        $token = cartVisitor();
        cartSeed($token, $variant, 1);

        $this->patch(route('cart.items.update', $variant->id), ['quantity' => 4])->assertSessionHasErrors('quantity');

        expect(CartItem::query()->where('cart_token', $token)->sole()->quantity)->toBe(1);
    });

    it('cannot change a line of another visitor', function () {
        $variant = cartVariant();
        $other = cartSeed(Str::random(40), $variant, 1);
        cartVisitor();

        $this->patch(route('cart.items.update', $variant->id), ['quantity' => 5])->assertSessionHasErrors('cart');
        $this->delete(route('cart.items.destroy', $variant->id))->assertRedirect();

        expect($other->fresh()->quantity)->toBe(1);
    });

    it('removes a line', function () {
        $keep = cartVariant();
        $drop = cartVariant();
        $token = cartVisitor();
        cartSeed($token, $keep);
        cartSeed($token, $drop);

        $this->delete(route('cart.items.destroy', $drop->id))->assertRedirect();

        expect(CartItem::query()->where('cart_token', $token)->pluck('product_variant_id')->all())->toBe([$keep->id]);
    });

    it('removes a line whose variant was deleted', function () {
        $variant = cartVariant();
        $token = cartVisitor();
        cartSeed($token, $variant);
        $variant->delete();

        $this->delete(route('cart.items.destroy', $variant->id))->assertRedirect();

        expect(CartItem::query()->where('cart_token', $token)->count())->toBe(0);
    });

    it('answers 404 for a non-numeric variant', function () {
        $this->delete('/cart/items/abc')->assertNotFound();
    });
});

describe('cart page', function () {
    it('shows current database prices and the payable subtotal', function () {
        $token = cartVisitor();
        $variant = cartVariant(price: 100000);
        cartSeed($token, $variant, 2, priceAtAdd: 100000);
        $variant->update(['price' => 120000]);

        $props = cartPage();

        expect($props['lines'][0])->toMatchArray([
            'variant_id' => $variant->id,
            'unit_price' => 120000,
            'quantity' => 2,
            'line_total' => 240000,
            'price_changed' => true,
            'previous_price' => 100000,
            'unavailable' => false,
        ])->and($props['subtotal'])->toBe(240000);
    });

    it('flags unavailable lines and leaves them out of the subtotal', function (Closure $break) {
        $token = cartVisitor();
        $ok = cartVariant(price: 50000);
        $broken = cartVariant(price: 80000, stock: 5);
        cartSeed($token, $ok, 1);
        cartSeed($token, $broken, 2);
        $break($broken);

        $lines = collect(cartPage()['lines'])->keyBy('variant_id');

        expect($lines[$broken->id]['unavailable'])->toBeTrue()
            ->and($lines[$ok->id]['unavailable'])->toBeFalse()
            ->and(cartPage()['subtotal'])->toBe(50000);
    })->with([
        'inactive variant' => [fn (ProductVariant $variant) => $variant->update(['status' => 'inactive'])],
        'inactive product' => [fn (ProductVariant $variant) => $variant->product->update(['status' => 'inactive'])],
        'sold out' => [fn (ProductVariant $variant) => $variant->update(['stock' => 0])],
        'less stock than in the cart' => [fn (ProductVariant $variant) => $variant->update(['stock' => 1])],
    ]);

    it('drops lines whose variant or product was deleted', function (Closure $delete) {
        $token = cartVisitor();
        $variant = cartVariant();
        cartSeed($token, $variant);
        $delete($variant);

        expect(cartPage()['lines'])->toBe([])
            ->and(CartItem::query()->where('cart_token', $token)->count())->toBe(0);
    })->with([
        'variant' => [fn (ProductVariant $variant) => $variant->delete()],
        'product' => [fn (ProductVariant $variant) => $variant->product->delete()],
    ]);

    it('lists the variant label, caps the quantity at the stock and asks search engines not to index it', function () {
        $token = cartVisitor();
        $variant = cartVariant(stock: 3);
        cartSeed($token, $variant, 1);

        $this->get(route('cart'))->assertInertia(fn (Assert $page) => $page
            ->component('Cart', false)
            ->where('lines.0.variant_label', null)
            ->where('lines.0.max_quantity', 3)
            ->where('seo.noindex', true)
            ->has('deliveryZones', 2));
    });

    it('is empty for a visitor without a cart', function () {
        expect(cartPage()['lines'])->toBe([])->and(cartPage()['subtotal'])->toBe(0);
    });
});

describe('throttle', function () {
    it('allows 30 cart requests a minute per IP and then answers 429', function () {
        RateLimiter::clear('cart');
        $variant = cartVariant(stock: 100);
        cartVisitor();

        foreach (range(1, 30) as $ignored) {
            $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 1])->assertRedirect();
        }

        $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 1])->assertStatus(429);
    });
});

describe('prune command', function () {
    it('deletes cart items untouched for 30 days and keeps the rest', function () {
        $this->freezeTime();
        $old = cartSeed('a'.Str::random(39), cartVariant());
        $fresh = cartSeed('b'.Str::random(39), cartVariant());
        CartItem::query()->whereKey($old->id)->update(['updated_at' => now()->subDays(31)]);

        $this->artisan('cart:prune')->assertSuccessful();

        expect(CartItem::query()->pluck('id')->all())->toBe([$fresh->id]);
    });
});

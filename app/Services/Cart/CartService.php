<?php

namespace App\Services\Cart;

use App\Enums\ActiveStatus;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\Media\FileUploadService;
use Illuminate\Validation\ValidationException;

/**
 * The guest cart (table `cart_items`, owned by the cookie token of CartToken). Prices and stock are always
 * read fresh from the database; the only stored price is the one at add time, to flag later changes.
 *
 * Failures are ValidationExceptions keyed `variant`, `quantity` or `cart`.
 */
class CartService
{
    private ?int $count = null;

    public function __construct(
        private readonly CartToken $token,
        private readonly FileUploadService $uploads,
    ) {}

    /**
     * Adds `$quantity` to the line of this variant (a new line when there is none yet).
     *
     * @throws ValidationException
     */
    public function add(int $variantId, int $quantity): void
    {
        $variant = $this->purchasable($variantId);
        $existing = $this->find($variantId);

        $this->guardQuantity($variant, ($existing?->quantity ?? 0) + $quantity);

        if ($existing === null && $this->lineCount() >= (int) config('shop.max_cart_lines')) {
            throw ValidationException::withMessages(['cart' => 'Your cart is full. Remove an item to add another.']);
        }

        CartItem::query()->updateOrCreate(
            ['cart_token' => $this->token->issue(), 'product_variant_id' => $variantId],
            [
                'quantity' => ($existing?->quantity ?? 0) + $quantity,
                'price_at_add' => $existing?->price_at_add ?? $variant->effective_price,
            ],
        );

        $this->count = null;
    }

    /**
     * Sets the quantity of a line. The stored price is refreshed: the customer is looking at the current one.
     *
     * @throws ValidationException
     */
    public function update(int $variantId, int $quantity): void
    {
        $item = $this->find($variantId) ?? throw ValidationException::withMessages(['cart' => 'This item is not in your cart.']);
        $variant = $this->purchasable($variantId);

        $this->guardQuantity($variant, $quantity);

        $item->update(['quantity' => $quantity, 'price_at_add' => $variant->effective_price]);
        $this->count = null;
    }

    public function remove(int $variantId): void
    {
        $this->forget([$variantId]);
    }

    /**
     * @param  list<int>  $variantIds
     */
    public function forget(array $variantIds): void
    {
        $token = $this->token->current();

        if ($token !== null && $variantIds !== []) {
            CartItem::query()->where('cart_token', $token)->whereIn('product_variant_id', $variantIds)->delete();
            $this->count = null;
        }
    }

    public function clear(): void
    {
        $token = $this->token->current();

        if ($token !== null) {
            CartItem::query()->where('cart_token', $token)->delete();
            $this->count = null;
        }
    }

    /**
     * Total quantity in the cart. One indexed query per request, none without a cookie.
     */
    public function count(): int
    {
        $token = $this->token->current();

        if ($token === null) {
            return 0;
        }

        return $this->count ??= (int) CartItem::query()->where('cart_token', $token)->sum('quantity');
    }

    /**
     * The cart as the customer sees it now. Lines whose variant or product no longer exists are dropped.
     *
     * @return list<array{variant_id: int, product_name: string, slug: string, variant_label: ?string, image_url: ?string, unit_price: int, quantity: int, line_total: int, stock: int, max_quantity: int, price_changed: bool, previous_price: int, unavailable: bool}>
     */
    public function lines(): array
    {
        $token = $this->token->current();

        if ($token === null) {
            return [];
        }

        $items = CartItem::query()
            ->where('cart_token', $token)
            ->with(['variant.product.mainImage', 'variant.image', 'variant.variantValues'])
            ->orderBy('id')
            ->get();

        [$live, $gone] = $items->partition(fn (CartItem $item) => $item->variant?->product !== null);

        if ($gone->isNotEmpty()) {
            CartItem::query()->whereKey($gone->modelKeys())->delete();
            $this->count = null;
        }

        return $live->map(fn (CartItem $item) => $this->line($item))->values()->all();
    }

    /**
     * The lines that can be ordered right now.
     *
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    public function payable(array $lines): array
    {
        return array_values(array_filter($lines, fn (array $line) => ! $line['unavailable']));
    }

    /**
     * Sum of the payable lines (minor units).
     *
     * @param  list<array<string, mixed>>  $lines
     */
    public function subtotal(array $lines): int
    {
        return array_sum(array_column($this->payable($lines), 'line_total'));
    }

    /**
     * @return array<string, mixed>
     */
    private function line(CartItem $item): array
    {
        $variant = $item->variant;
        $product = $variant->product;
        $stock = $variant->stock;
        $unitPrice = $variant->effective_price;

        return [
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'slug' => $product->slug,
            'variant_label' => $variant->combination_key === ProductVariant::DEFAULT_COMBINATION ? null : $variant->label,
            'image_url' => $this->uploads->variantUrl($variant->image?->path ?? $product->mainImage?->path, 'thumb'),
            'unit_price' => $unitPrice,
            'quantity' => $item->quantity,
            'line_total' => $unitPrice * $item->quantity,
            'stock' => $stock,
            'max_quantity' => max(1, min($stock, (int) config('shop.max_qty_per_item'))),
            'price_changed' => $unitPrice !== $item->price_at_add,
            'previous_price' => $item->price_at_add,
            'unavailable' => $variant->status !== ActiveStatus::Active
                || $product->status !== ActiveStatus::Active
                || $item->quantity > $stock,
        ];
    }

    private function find(int $variantId): ?CartItem
    {
        $token = $this->token->current();

        return $token === null
            ? null
            : CartItem::query()->where('cart_token', $token)->where('product_variant_id', $variantId)->first();
    }

    private function lineCount(): int
    {
        $token = $this->token->current();

        return $token === null ? 0 : CartItem::query()->where('cart_token', $token)->count();
    }

    /**
     * @throws ValidationException when the variant is missing, inactive or sold out
     */
    private function purchasable(int $variantId): ProductVariant
    {
        $variant = ProductVariant::query()->with('product:id,status')->find($variantId);

        if ($variant === null || $variant->status !== ActiveStatus::Active || $variant->product?->status !== ActiveStatus::Active) {
            throw ValidationException::withMessages(['variant' => 'This item is no longer available.']);
        }

        return $variant;
    }

    /**
     * @throws ValidationException
     */
    private function guardQuantity(ProductVariant $variant, int $quantity): void
    {
        $max = (int) config('shop.max_qty_per_item');

        if ($quantity < 1 || $quantity > $max) {
            throw ValidationException::withMessages(['quantity' => "You can order at most {$max} of an item."]);
        }

        if ($quantity > $variant->stock) {
            throw ValidationException::withMessages([
                'quantity' => $variant->stock > 0 ? "Only {$variant->stock} left in stock." : 'This item is out of stock.',
            ]);
        }
    }
}

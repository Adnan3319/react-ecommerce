<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddToCartRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Services\Cart\CartService;
use App\Services\Order\DeliveryZones;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly DeliveryZones $zones,
    ) {}

    public function show(): Response
    {
        $lines = $this->cart->lines();

        return Inertia::render('Cart', [
            'lines' => $lines,
            'subtotal' => $this->cart->subtotal($lines),
            'deliveryZones' => $this->zones->forStorefront(),
            'maxQty' => (int) config('shop.max_qty_per_item'),
            'seo' => [
                'title' => 'Your cart',
                'description' => null,
                'canonical' => null,
                'noindex' => true,
                'image' => null,
                'jsonLd' => [],
            ],
        ]);
    }

    public function store(AddToCartRequest $request): RedirectResponse
    {
        $this->cart->add($request->integer('variant_id'), $request->integer('quantity'));

        Inertia::flash('success', 'Added to cart.');

        return back();
    }

    public function update(UpdateCartItemRequest $request, int $variantId): RedirectResponse
    {
        $this->cart->update($variantId, $request->integer('quantity'));

        return back();
    }

    public function destroy(int $variantId): RedirectResponse
    {
        $this->cart->remove($variantId);

        return back();
    }
}

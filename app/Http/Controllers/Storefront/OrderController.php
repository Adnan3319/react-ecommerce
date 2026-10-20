<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\PlaceOrderRequest;
use App\Http\Resources\Storefront\OrderSuccessResource;
use App\Services\Order\CheckoutService;
use App\Services\Order\OrderLookup;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly OrderLookup $orders,
    ) {}

    public function store(PlaceOrderRequest $request): RedirectResponse
    {
        $order = $this->checkout->place($request->checkoutData());

        return redirect()->route('orders.success', $order->public_token);
    }

    public function show(string $token): Response
    {
        $order = $this->orders->forSuccessPage($token);

        abort_if($order === null, 404);

        return Inertia::render('Orders/Success', [
            'order' => OrderSuccessResource::make($order)->resolve(),
            'seo' => [
                'title' => 'Order '.$order->number,
                'description' => null,
                'canonical' => null,
                'noindex' => true,
                'image' => null,
                'jsonLd' => [],
            ],
        ]);
    }
}

<?php

namespace App\Http\Requests\Storefront;

use App\Enums\OrderSource;
use App\Http\Middleware\CaptureAttribution;
use App\Services\Cart\CartService;
use App\Services\Order\CheckoutData;
use App\Services\Order\DeliveryZones;
use App\Support\Phone;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The order form of the product page (one variant) and of the Cart page (the lines of the server-side cart),
 * with guest details and delivery area. Prices are never read from the request, and a cart order takes no
 * items from the client at all.
 */
class PlaceOrderRequest extends FormRequest
{
    private const SINGLE_ITEM = 1;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'phone' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if (Phone::normalize((string) $value) === null) {
                    $fail('Enter a valid mobile number, e.g. 01XXXXXXXXX.');
                }
            }],
            'address' => ['required', 'string', 'min:10', 'max:500'],
            'delivery_zone' => ['required', 'string', Rule::in(app(DeliveryZones::class)->keys())],
            'note' => ['nullable', 'string', 'max:500'],
            'source' => ['nullable', Rule::in([OrderSource::BuyNow->value, OrderSource::Cart->value])],
            ...$this->itemRules(),
            // Honeypot: real visitors never see this field.
            'website' => ['nullable', 'max:0'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function isCartOrder(): bool
    {
        return $this->input('source') === OrderSource::Cart->value;
    }

    public function checkoutData(): CheckoutData
    {
        $input = $this->validated();
        $attribution = (array) $this->session()->get(CaptureAttribution::SESSION_KEY, []);

        return new CheckoutData(
            items: $this->isCartOrder() ? $this->cartItems() : $this->buyNowItems($input['items']),
            name: $input['name'],
            phone: $input['phone'],
            address: $input['address'],
            deliveryZone: $input['delivery_zone'],
            idempotencyKey: $input['idempotency_key'],
            source: $this->isCartOrder() ? OrderSource::Cart : OrderSource::BuyNow,
            note: $input['note'] ?? null,
            utmSource: $attribution['utm_source'] ?? null,
            utmMedium: $attribution['utm_medium'] ?? null,
            utmCampaign: $attribution['utm_campaign'] ?? null,
            fbclid: $attribution['fbclid'] ?? null,
            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function itemRules(): array
    {
        if ($this->isCartOrder()) {
            return ['items' => ['prohibited']];
        }

        return [
            'items' => ['required', 'array', 'size:'.self::SINGLE_ITEM],
            'items.*.variant_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @param  array<int, array{variant_id: mixed, quantity: mixed}>  $items
     * @return list<array{variant_id: int, quantity: int}>
     */
    private function buyNowItems(array $items): array
    {
        return array_map(fn (array $item) => [
            'variant_id' => (int) $item['variant_id'],
            'quantity' => (int) $item['quantity'],
        ], array_values($items));
    }

    /**
     * The orderable lines of the visitor's own cart; an empty list is refused by CheckoutService.
     *
     * @return list<array{variant_id: int, quantity: int}>
     */
    private function cartItems(): array
    {
        $cart = app(CartService::class);

        return array_map(fn (array $line) => [
            'variant_id' => $line['variant_id'],
            'quantity' => $line['quantity'],
        ], $cart->payable($cart->lines()));
    }
}

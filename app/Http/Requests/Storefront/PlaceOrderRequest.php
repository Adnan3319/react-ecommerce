<?php

namespace App\Http\Requests\Storefront;

use App\Enums\OrderSource;
use App\Http\Middleware\CaptureAttribution;
use App\Services\Order\CheckoutData;
use App\Services\Order\DeliveryZones;
use App\Support\Phone;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The Order Now form of the product page: one variant, guest details, delivery area.
 * Prices are never read from the request.
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
            'items' => ['required', 'array', 'size:'.self::SINGLE_ITEM],
            'items.*.variant_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            // Honeypot: real visitors never see this field.
            'website' => ['nullable', 'max:0'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function checkoutData(): CheckoutData
    {
        $input = $this->validated();
        $attribution = (array) $this->session()->get(CaptureAttribution::SESSION_KEY, []);

        return new CheckoutData(
            items: array_map(fn (array $item) => [
                'variant_id' => (int) $item['variant_id'],
                'quantity' => (int) $item['quantity'],
            ], array_values($input['items'])),
            name: $input['name'],
            phone: $input['phone'],
            address: $input['address'],
            deliveryZone: $input['delivery_zone'],
            idempotencyKey: $input['idempotency_key'],
            source: OrderSource::BuyNow,
            note: $input['note'] ?? null,
            utmSource: $attribution['utm_source'] ?? null,
            utmMedium: $attribution['utm_medium'] ?? null,
            utmCampaign: $attribution['utm_campaign'] ?? null,
            fbclid: $attribution['fbclid'] ?? null,
            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),
        );
    }
}

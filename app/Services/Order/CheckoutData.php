<?php

namespace App\Services\Order;

use App\Enums\OrderSource;

/**
 * What the order form submits. There is deliberately no price or total: those always
 * come from the database.
 */
final readonly class CheckoutData
{
    /**
     * @param  list<array{variant_id: int, quantity: int}>  $items
     */
    public function __construct(
        public array $items,
        public string $name,
        public string $phone,
        public string $address,
        public string $deliveryZone,
        public string $idempotencyKey,
        public OrderSource $source,
        public ?string $note = null,
        public ?string $utmSource = null,
        public ?string $utmMedium = null,
        public ?string $utmCampaign = null,
        public ?string $fbclid = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}
}

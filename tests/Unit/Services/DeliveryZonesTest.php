<?php

use App\Services\Order\DeliveryZones;

beforeEach(function () {
    config(['shop.delivery_zones' => [
        'inside' => ['label' => 'Inside City', 'charge' => 7000],
        'outside' => ['label' => 'Outside City', 'charge' => 13000],
    ]]);

    $this->zones = new DeliveryZones;
});

describe('reading config', function () {
    it('lists all zones and their keys', function () {
        expect($this->zones->keys())->toBe(['inside', 'outside'])
            ->and($this->zones->all())->toHaveCount(2);
    });

    it('returns the label and charge of a zone', function () {
        expect($this->zones->get('outside'))->toBe(['label' => 'Outside City', 'charge' => 13000]);
    });

    it('returns the charge in minor units', function () {
        expect($this->zones->charge('inside'))->toBe(7000);
    });

    it('follows a changed config', function () {
        config(['shop.delivery_zones.inside.charge' => 9000]);

        expect($this->zones->charge('inside'))->toBe(9000);
    });
});

describe('unknown zone', function () {
    it('throws for get and charge', function (string $method) {
        $this->zones->{$method}('mars');
    })->with(['get', 'charge'])->throws(InvalidArgumentException::class, 'Unknown delivery zone [mars].');
});

it('ships the two default zones in config', function () {
    $defaults = require config_path('shop.php');

    expect(array_keys($defaults['delivery_zones']))->toBe(['inside', 'outside']);
});

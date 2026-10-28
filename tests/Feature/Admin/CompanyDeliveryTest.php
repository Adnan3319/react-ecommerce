<?php

use App\Models\Company;
use App\Services\Order\DeliveryZones;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Company::create(['name' => 'Checkit']);
});

function companyPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Checkit',
        'city' => 'Chattogram',
        'delivery_charge_inside' => '60',
        'delivery_charge_outside' => '120.50',
    ], $overrides);
}

it('shows the saved charges in taka on the edit page', function () {
    actingAsAdmin()->get(route('admin.company.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('company.city', 'Dhaka')
            ->where('company.delivery_charge_inside', '70')
            ->where('company.delivery_charge_outside', '130'));
});

it('stores the charges in minor units', function () {
    actingAsAdmin()->post(route('admin.company.update'), companyPayload())->assertRedirect();

    expect(Company::first())
        ->delivery_charge_inside->toBe(6000)
        ->delivery_charge_outside->toBe(12050);
});

it('names the delivery zones after the saved city and uses the saved charges', function () {
    actingAsAdmin()->post(route('admin.company.update'), companyPayload());

    expect(app(DeliveryZones::class)->forStorefront())->toBe([
        ['key' => 'inside', 'label' => 'Inside Chattogram', 'charge' => 6000],
        ['key' => 'outside', 'label' => 'Outside Chattogram', 'charge' => 12050],
    ]);
});

it('picks up a changed city after an earlier read cached the zones', function () {
    app(DeliveryZones::class)->all();

    actingAsAdmin()->post(route('admin.company.update'), companyPayload(['city' => 'Sylhet']));

    expect(app(DeliveryZones::class)->get('outside')['label'])->toBe('Outside Sylhet');
});

it('rejects a missing city and an invalid charge', function (array $overrides, string $field) {
    actingAsAdmin()->post(route('admin.company.update'), companyPayload($overrides))
        ->assertSessionHasErrors($field);
})->with([
    'no city' => [['city' => ''], 'city'],
    'negative charge' => [['delivery_charge_inside' => '-5'], 'delivery_charge_inside'],
    'text charge' => [['delivery_charge_outside' => 'free'], 'delivery_charge_outside'],
]);

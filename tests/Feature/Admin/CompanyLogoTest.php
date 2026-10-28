<?php

use App\Models\Company;
use App\Services\Company\CompanyBranding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Storage::fake('uploads');
    Company::create(['name' => 'Rahim Store']);
});

function logoPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Rahim Store',
        'city' => 'Dhaka',
        'delivery_charge_inside' => '70',
        'delivery_charge_outside' => '130',
    ], $overrides);
}

it('uses the store name and a letter favicon until a logo is uploaded', function () {
    $branding = app(CompanyBranding::class)->data();

    expect($branding)
        ->name->toBe('Rahim Store')
        ->logo_url->toBeNull()
        ->is_custom->toBeFalse()
        ->and($branding['favicon_url'])->toStartWith('data:image/svg+xml,')
        ->and(rawurldecode($branding['favicon_url']))->toContain('>R</text>');
});

it('stores an uploaded logo and uses it everywhere', function () {
    actingAsAdmin()->post(route('admin.company.update'), logoPayload(['logo' => fakeImage(600, 300)]))->assertRedirect();

    $path = Company::first()->logo;
    $branding = app(CompanyBranding::class)->data();

    Storage::disk('uploads')->assertExists($path);
    expect($branding['is_custom'])->toBeTrue()
        ->and($branding['logo_url'])->toBe($branding['favicon_url']);
});

it('shares the branding with the storefront and the admin', function () {
    $this->get(route('home'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('branding.name', 'Rahim Store')
        ->where('branding.logo_url', null));
});

it('puts the favicon into the server-rendered page', function () {
    actingAsAdmin()->post(route('admin.company.update'), logoPayload(['logo' => fakeImage(600, 300)]));
    $favicon = app(CompanyBranding::class)->data()['favicon_url'];

    $this->get(route('home'))->assertSee('<link rel="icon" href="'.$favicon.'"', false);
});

it('replaces the logo and deletes the old file', function () {
    actingAsAdmin()->post(route('admin.company.update'), logoPayload(['logo' => fakeImage(600, 300)]));
    $old = Company::first()->logo;

    actingAsAdmin()->post(route('admin.company.update'), logoPayload(['logo' => fakeImage(400, 400)]));

    Storage::disk('uploads')->assertMissing($old);
    expect(Company::first()->logo)->not->toBe($old);
});

it('goes back to the default when the logo is removed', function () {
    actingAsAdmin()->post(route('admin.company.update'), logoPayload(['logo' => fakeImage(600, 300)]));
    $old = Company::first()->logo;

    actingAsAdmin()->post(route('admin.company.update'), logoPayload(['remove_logo' => true]))->assertRedirect();

    Storage::disk('uploads')->assertMissing($old);
    expect(Company::first()->logo)->toBeNull()
        ->and(app(CompanyBranding::class)->data()['logo_url'])->toBeNull();
});

it('keeps the logo when the update sends none', function () {
    actingAsAdmin()->post(route('admin.company.update'), logoPayload(['logo' => fakeImage(600, 300)]));
    $logo = Company::first()->logo;

    actingAsAdmin()->post(route('admin.company.update'), logoPayload(['name' => 'Renamed']))->assertRedirect();

    expect(Company::first()->logo)->toBe($logo)
        ->and(app(CompanyBranding::class)->data()['name'])->toBe('Renamed');
});

it('rejects a logo that is not an image', function () {
    actingAsAdmin()->post(route('admin.company.update'), logoPayload(['logo' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf')]))
        ->assertSessionHasErrors('logo');
});

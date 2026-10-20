<?php

use App\Models\Product;
use App\Models\VariantHead;
use App\Models\VariantValue;
use Illuminate\Database\QueryException;

describe('heads', function () {
    it('belongs to a product and lists its values by position', function () {
        $head = VariantHead::factory()->create(['name' => 'Size']);
        VariantValue::factory()->for($head, 'head')->create(['value' => 'L', 'position' => 2]);
        VariantValue::factory()->for($head, 'head')->create(['value' => 'M', 'position' => 1]);

        expect($head->product)->toBeInstanceOf(Product::class)
            ->and($head->values->pluck('value')->all())->toBe(['M', 'L']);
    });

    it('rejects a second head with the same name on one product', function () {
        $product = Product::factory()->create();
        VariantHead::factory()->for($product)->create(['name' => 'Size', 'position' => 0]);

        expect(fn () => VariantHead::factory()->for($product)->create(['name' => 'Size', 'position' => 1]))
            ->toThrow(QueryException::class);
    });

    it('rejects two heads at the same position on one product', function () {
        $product = Product::factory()->create();
        VariantHead::factory()->for($product)->create(['name' => 'Size', 'position' => 0]);

        expect(fn () => VariantHead::factory()->for($product)->create(['name' => 'Color', 'position' => 0]))
            ->toThrow(QueryException::class);
    });

    it('allows the same head name on another product', function () {
        VariantHead::factory()->create(['name' => 'Size']);
        VariantHead::factory()->create(['name' => 'Size']);

        expect(VariantHead::where('name', 'Size')->count())->toBe(2);
    });

    it('removes its values when the head is deleted', function () {
        $head = VariantHead::factory()->create();
        VariantValue::factory()->for($head, 'head')->create();

        $head->delete();

        expect(VariantValue::count())->toBe(0);
    });
});

describe('values', function () {
    it('rejects a duplicate value under one head', function () {
        $head = VariantHead::factory()->create();
        VariantValue::factory()->for($head, 'head')->create(['value' => 'M']);

        expect(fn () => VariantValue::factory()->for($head, 'head')->create(['value' => 'M']))
            ->toThrow(QueryException::class);
    });

    it('allows the same value under another head', function () {
        VariantValue::factory()->create(['value' => 'Blue']);
        VariantValue::factory()->create(['value' => 'Blue']);

        expect(VariantValue::where('value', 'Blue')->count())->toBe(2);
    });

    it('knows its head', function () {
        $head = VariantHead::factory()->create(['name' => 'Color']);
        $value = VariantValue::factory()->for($head, 'head')->create();

        expect($value->head->name)->toBe('Color');
    });
});

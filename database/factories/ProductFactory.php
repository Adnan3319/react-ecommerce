<?php

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantHead;
use App\Models\VariantValue;
use App\Services\Product\SkuGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Creates products without variants; add them with simple(), withVariants() or withOptions().
 *
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    private const DEFAULT_PRICE = 150000;

    private const SIZES = ['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL'];

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => ActiveStatus::Active,
            'is_featured' => false,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    /**
     * One default variant (no options). Price in minor units.
     */
    public function simple(int $price = self::DEFAULT_PRICE, int $stock = 10): static
    {
        return $this->afterCreating(function (Product $product) use ($price, $stock): void {
            ProductVariant::factory()->for($product)->create([
                'sku' => app(SkuGenerator::class)->generate($product),
                'price' => $price,
                'stock' => $stock,
            ]);
        });
    }

    /**
     * One head "Size" with $count values (S, M, L, XL, ...), one variant per value.
     */
    public function withVariants(int $count, int $stock = 10, int $price = self::DEFAULT_PRICE): static
    {
        $sizes = array_map(
            fn (int $index) => self::SIZES[$index] ?? 'Size '.($index + 1),
            range(0, max($count, 1) - 1),
        );

        return $this->withOptions(['Size' => $sizes], $price, $stock);
    }

    /**
     * Heads with their values and one variant per combination, e.g.
     * ['Size' => ['M', 'L'], 'Color' => ['Pink', 'Black']] gives four variants.
     *
     * @param  array<string, array<int, string>>  $heads
     */
    public function withOptions(array $heads, int $price = self::DEFAULT_PRICE, int $stock = 10): static
    {
        return $this->afterCreating(function (Product $product) use ($heads, $price, $stock): void {
            $valueLists = [];

            foreach (array_keys($heads) as $headIndex => $name) {
                $head = VariantHead::factory()->for($product)->create(['name' => $name, 'position' => $headIndex]);

                $valueLists[] = collect($heads[$name])->values()->map(
                    fn (string $value, int $position) => VariantValue::factory()->for($head, 'head')->create([
                        'value' => $value,
                        'position' => $position,
                    ])
                )->all();
            }

            $reserved = [];

            foreach ($this->combinations($valueLists) as $position => $values) {
                $sku = app(SkuGenerator::class)->generate($product, array_map(fn (VariantValue $v) => $v->value, $values), $reserved);
                $reserved[] = $sku;

                $variant = ProductVariant::factory()->for($product)->create([
                    'sku' => $sku,
                    'price' => $price,
                    'stock' => $stock,
                    'sort_order' => $position,
                ]);

                $variant->syncVariantValues(array_map(fn (VariantValue $v) => $v->id, $values));
            }
        });
    }

    /**
     * A simple product whose only variant has no stock.
     */
    public function outOfStock(): static
    {
        return $this->simple(stock: 0);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive]);
    }

    /**
     * Cartesian product of the value lists, one entry per variant.
     *
     * @param  array<int, array<int, VariantValue>>  $lists
     * @return array<int, array<int, VariantValue>>
     */
    private function combinations(array $lists): array
    {
        $result = [[]];

        foreach ($lists as $list) {
            $next = [];

            foreach ($result as $combination) {
                foreach ($list as $value) {
                    $next[] = [...$combination, $value];
                }
            }

            $result = $next;
        }

        return $result;
    }
}

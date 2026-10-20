<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * What is actually sold: price, stock and SKU live here. A simple product has exactly one
 * variant with no selected values (the "default" variant).
 */
class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    /** combination_key of a variant that has no selected values. */
    public const DEFAULT_COMBINATION = 'default';

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'sale_price',
        'stock',
        'product_image_id',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'sale_price' => 'integer',
            'stock' => 'integer',
            'sort_order' => 'integer',
            'status' => ActiveStatus::class,
        ];
    }

    /**
     * Stable key for a set of selected value ids: sorted ascending and joined with "-".
     * Used by the unique (product_id, live_key) index to allow one live variant per combination.
     *
     * @param  array<int, int|string>  $valueIds
     */
    public static function combinationKeyFor(array $valueIds): string
    {
        $ids = array_map('intval', $valueIds);
        sort($ids);

        return $ids === [] ? self::DEFAULT_COMBINATION : implode('-', $ids);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductImage, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'product_image_id');
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * The values this variant selected, in the order of their heads (Size, then Color).
     *
     * @return BelongsToMany<VariantValue, $this>
     */
    public function variantValues(): BelongsToMany
    {
        return $this->belongsToMany(VariantValue::class, 'variant_selections')
            ->join('variant_heads', 'variant_heads.id', '=', 'variant_values.variant_head_id')
            ->orderBy('variant_heads.position');
    }

    /**
     * Replaces the selected values and the combination key together, so they can never disagree.
     * A duplicate combination for the same product fails on the unique index and rolls both back.
     *
     * @param  array<int, int|string>  $valueIds
     */
    public function syncVariantValues(array $valueIds): void
    {
        DB::transaction(function () use ($valueIds): void {
            $this->variantValues()->sync($valueIds);

            $this->forceFill(['combination_key' => self::combinationKeyFor($valueIds)])->save();
        });

        $this->unsetRelation('variantValues');
    }

    /**
     * @param  Builder<ProductVariant>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', ActiveStatus::Active);
    }

    /**
     * Sale price when there is one, otherwise the regular price (minor units).
     *
     * @return Attribute<int, never>
     */
    protected function effectivePrice(): Attribute
    {
        return Attribute::get(fn (): int => $this->sale_price ?? $this->price);
    }

    /**
     * Whole-number percentage saved (2000 → 1650 = 18); null when there is no sale.
     *
     * @return Attribute<?int, never>
     */
    protected function discountPercent(): Attribute
    {
        return Attribute::get(function (): ?int {
            if ($this->sale_price === null || $this->price <= 0) {
                return null;
            }

            return (int) round(($this->price - $this->sale_price) / $this->price * 100);
        });
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isOutOfStock(): Attribute
    {
        return Attribute::get(fn (): bool => $this->stock <= 0);
    }

    /**
     * "M / Pink" (values in head order) or "Default" for a variant without values.
     *
     * @return Attribute<string, never>
     */
    protected function label(): Attribute
    {
        return Attribute::get(function (): string {
            $values = $this->variantValues->pluck('value');

            return $values->isEmpty() ? 'Default' : $values->implode(' / ');
        });
    }
}

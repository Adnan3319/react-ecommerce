<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasAuditFields, HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'status',
        'is_featured',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
            'is_featured' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_main')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasOne<ProductImage, $this>
     */
    public function mainImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_main', true);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function galleryImages(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('is_main', false)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The option names of this product (Size, Color), in display order.
     *
     * @return HasMany<VariantHead, $this>
     */
    public function variantHeads(): HasMany
    {
        return $this->hasMany(VariantHead::class)->orderBy('position');
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function activeVariants(): HasMany
    {
        return $this->variants()->active();
    }

    /**
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', ActiveStatus::Active);
    }

    /**
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function featured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * Products with at least one active variant that has stock.
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function inStock(Builder $query): void
    {
        $query->whereHas('variants', fn (Builder $variants) => $variants->active()->where('stock', '>', 0));
    }

    /**
     * Cheapest and dearest effective price over the active variants (nulls when there are none).
     *
     * @return Attribute<array{min: ?int, max: ?int}, never>
     */
    protected function priceRange(): Attribute
    {
        return Attribute::get(function (): array {
            $prices = $this->activeVariantList()->map(fn (ProductVariant $variant) => $variant->effective_price);

            return ['min' => $prices->min(), 'max' => $prices->max()];
        });
    }

    /**
     * @return Attribute<int, never>
     */
    protected function totalStock(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->activeVariantList()->sum('stock'));
    }

    /**
     * True when the product has option names (Size, Color); a simple product only has its default variant.
     *
     * @return Attribute<bool, never>
     */
    protected function hasVariants(): Attribute
    {
        return Attribute::get(fn (): bool => $this->relationLoaded('variantHeads')
            ? $this->variantHeads->isNotEmpty()
            : $this->variantHeads()->exists());
    }

    /**
     * Active variants, reusing whichever relation is already loaded to avoid extra queries.
     *
     * @return Collection<int, ProductVariant>
     */
    private function activeVariantList(): Collection
    {
        if ($this->relationLoaded('activeVariants')) {
            return $this->activeVariants;
        }

        if ($this->relationLoaded('variants')) {
            return $this->variants->filter(fn (ProductVariant $variant) => $variant->status === ActiveStatus::Active)->values();
        }

        return $this->activeVariants;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An option name of one product (Size, Color).
 */
class VariantHead extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<VariantValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(VariantValue::class)->orderBy('position')->orderBy('id');
    }
}

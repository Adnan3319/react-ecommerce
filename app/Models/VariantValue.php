<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One value of a variant head (M under Size, Pink under Color).
 */
class VariantValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'variant_head_id',
        'value',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<VariantHead, $this>
     */
    public function head(): BelongsTo
    {
        return $this->belongsTo(VariantHead::class, 'variant_head_id');
    }

    /**
     * @return BelongsToMany<ProductVariant, $this>
     */
    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'variant_selections');
    }
}

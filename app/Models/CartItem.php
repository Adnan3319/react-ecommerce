<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a guest cart, owned by the cart token of the visitor's cookie.
 */
class CartItem extends Model
{
    protected $fillable = [
        'cart_token',
        'product_variant_id',
        'quantity',
        'price_at_add',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price_at_add' => 'integer',
        ];
    }

    /**
     * Soft-deleted variants are not returned, which is how the cart notices that a variant is gone.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}

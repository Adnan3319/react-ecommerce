<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'is_blocked',
        'orders_count',
        'delivered_count',
        'cancelled_count',
    ];

    protected function casts(): array
    {
        return [
            'is_blocked' => 'boolean',
            'orders_count' => 'integer',
            'delivered_count' => 'integer',
            'cancelled_count' => 'integer',
        ];
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @param  Builder<Customer>  $query
     */
    #[Scope]
    protected function byPhone(Builder $query, string $phone): void
    {
        $query->where('phone', $phone);
    }
}

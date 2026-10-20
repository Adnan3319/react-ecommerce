<?php

namespace App\Models;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Support\Like;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'public_token',
        'customer_id',
        'status',
        'payment_method',
        'payment_status',
        'source',
        'subtotal',
        'delivery_charge',
        'total',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'delivery_zone',
        'delivery_zone_label',
        'customer_note',
        'idempotency_key',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'fbclid',
        'ip_address',
        'user_agent',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'source' => OrderSource::class,
            'subtotal' => 'integer',
            'delivery_charge' => 'integer',
            'total' => 'integer',
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function status(Builder $query, ?OrderStatus $status): void
    {
        $query->when($status, fn (Builder $q) => $q->where('orders.status', $status));
    }

    /**
     * Order number, customer name or phone contains the term (LIKE wildcards in the term match literally).
     *
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $pattern = '%'.Like::escape($term).'%';
        $escape = Like::ESCAPE;

        $query->where(fn (Builder $match) => $match
            ->whereRaw("orders.number LIKE ? ESCAPE '{$escape}'", [$pattern])
            ->orWhereRaw("orders.shipping_name LIKE ? ESCAPE '{$escape}'", [$pattern])
            ->orWhereRaw("orders.shipping_phone LIKE ? ESCAPE '{$escape}'", [$pattern]));
    }

    /**
     * Orders created from the start of `$from` to the end of `$to` (either may be null).
     *
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function placedBetween(Builder $query, ?CarbonInterface $from, ?CarbonInterface $to): void
    {
        $query
            ->when($from, fn (Builder $q) => $q->where('orders.created_at', '>=', $from->copy()->startOfDay()))
            ->when($to, fn (Builder $q) => $q->where('orders.created_at', '<=', $to->copy()->endOfDay()));
    }
}

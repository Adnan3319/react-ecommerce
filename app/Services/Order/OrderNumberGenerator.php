<?php

namespace App\Services\Order;

use App\Models\Order;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Order numbers ("CK-250131-0007": prefix, day, daily sequence) and public tokens.
 *
 * The number is read before the order exists, so two requests can pick the same one; the unique
 * index on `orders.number` is the real guard and createWithNumber() retries when it fires.
 */
class OrderNumberGenerator
{
    private const SEQUENCE_LENGTH = 4;

    private const TOKEN_LENGTH = 32;

    private const MAX_ATTEMPTS = 5;

    public function next(?CarbonInterface $at = null): string
    {
        $prefix = sprintf('%s-%s-', config('shop.order_number_prefix'), ($at ?? now())->format('ymd'));

        // Longest first, so a sequence past 9999 still sorts after 9999.
        $last = Order::query()
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc(DB::raw('LENGTH(number)'))
            ->orderByDesc('number')
            ->value('number');

        $sequence = $last === null ? 1 : (int) substr($last, strlen($prefix)) + 1;

        return $prefix.str_pad((string) $sequence, self::SEQUENCE_LENGTH, '0', STR_PAD_LEFT);
    }

    public function token(): string
    {
        return Str::random(self::TOKEN_LENGTH);
    }

    /**
     * Calls `$create` with a fresh number and retries with the next one when another request took it first.
     *
     * @template TOrder
     *
     * @param  Closure(string): TOrder  $create
     * @return TOrder
     */
    public function createWithNumber(Closure $create, ?CarbonInterface $at = null): mixed
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return $create($this->next($at));
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= self::MAX_ATTEMPTS || ! str_contains($e->getMessage(), 'number')) {
                    throw $e;
                }
            }
        }
    }
}

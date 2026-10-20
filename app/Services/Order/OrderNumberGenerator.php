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
 * The sequence of a day is one row in `order_number_sequences`. Taking a number locks that row until
 * the surrounding transaction ends, so concurrent orders get different numbers and a rolled-back
 * order gives its number back. Call next() inside the transaction that creates the order.
 * (Reading the highest existing number does not work: MySQL's transaction snapshot cannot see
 * orders committed after it started, so parallel requests would all pick the same number.)
 */
class OrderNumberGenerator
{
    private const SEQUENCE_LENGTH = 4;

    private const TOKEN_LENGTH = 32;

    private const MAX_ATTEMPTS = 5;

    private const SEQUENCE_TABLE = 'order_number_sequences';

    private const ATOMIC_DRIVERS = ['mysql', 'mariadb'];

    public function next(?CarbonInterface $at = null): string
    {
        $day = ($at ?? now())->format('ymd');
        $prefix = sprintf('%s-%s-', config('shop.order_number_prefix'), $day);

        $sequence = DB::transaction(fn () => $this->takeSequence($day, $prefix));

        return $prefix.str_pad((string) $sequence, self::SEQUENCE_LENGTH, '0', STR_PAD_LEFT);
    }

    public function token(): string
    {
        return Str::random(self::TOKEN_LENGTH);
    }

    /**
     * Calls `$create` with a fresh number. Numbers only collide when an order was created outside this
     * generator (an import, a factory); the unique index catches that and the next number is tried.
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

    private function takeSequence(string $day, string $prefix): int
    {
        return in_array(DB::getDriverName(), self::ATOMIC_DRIVERS, true)
            ? $this->takeSequenceAtomically($day, $prefix)
            : $this->takeSequenceWithLock($day, $prefix);
    }

    /**
     * MySQL: one statement creates the day's row or increments it, taking the row lock directly.
     * Selecting first and inserting when missing deadlocks when several orders open the day together.
     */
    private function takeSequenceAtomically(string $day, string $prefix): int
    {
        $seed = DB::table(self::SEQUENCE_TABLE)->where('day', $day)->exists() ? 1 : $this->highestExisting($prefix) + 1;

        DB::statement(
            'INSERT INTO '.self::SEQUENCE_TABLE.' (day, last_number) VALUES (?, LAST_INSERT_ID(?)) '
            .'ON DUPLICATE KEY UPDATE last_number = LAST_INSERT_ID(last_number + 1)',
            [$day, $seed],
        );

        return (int) DB::selectOne('SELECT LAST_INSERT_ID() AS sequence')->sequence;
    }

    /**
     * Other databases (the SQLite test suite): lock the row, or create it for the first order of the day.
     */
    private function takeSequenceWithLock(string $day, string $prefix): int
    {
        $row = DB::table(self::SEQUENCE_TABLE)->where('day', $day)->lockForUpdate()->first();

        if ($row === null) {
            try {
                DB::table(self::SEQUENCE_TABLE)->insert(['day' => $day, 'last_number' => $this->highestExisting($prefix) + 1]);
            } catch (UniqueConstraintViolationException) {
                return $this->takeSequenceWithLock($day, $prefix);
            }

            return DB::table(self::SEQUENCE_TABLE)->where('day', $day)->value('last_number');
        }

        DB::table(self::SEQUENCE_TABLE)->where('day', $day)->increment('last_number');

        return $row->last_number + 1;
    }

    /**
     * Highest sequence among the orders of the day, so orders made before the day's row existed keep their place.
     */
    private function highestExisting(string $prefix): int
    {
        return Order::query()->where('number', 'like', $prefix.'%')->pluck('number')
            ->map(fn (string $number) => (int) substr($number, strlen($prefix)))
            ->max() ?? 0;
    }
}

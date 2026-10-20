<?php

use App\Enums\StockMovementReason;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;

/*
 * Several PHP processes place orders at the same moment against real MySQL, which the SQLite suite
 * cannot do. Rows are committed for real (no wrapping transaction), so the Mysql suite truncates
 * the tables between tests instead of using RefreshDatabase.
 * Run: php artisan test -c phpunit-mysql.xml --group=mysql   (see testcase.md §3)
 */

// Seconds the processes get to boot before they all fire together.
const CONCURRENCY_BOOT_SECONDS = 4;

function concurrencyVariant(int $stock): ProductVariant
{
    return Product::factory()->simple(100000, $stock)->create()->variants()->sole();
}

/**
 * Starts one process per order list and waits for all of them.
 *
 * @param  array<string, array<int, int>>  $orders  phone => quantity by variant id
 * @return array<string, array<string, mixed>> the decoded result of each process by phone
 */
function concurrencyRun(array $orders): array
{
    $startAt = microtime(true) + CONCURRENCY_BOOT_SECONDS;
    $connection = config('database.connections.mysql');

    $results = Process::pool(function (Pool $pool) use ($orders, $startAt, $connection) {
        foreach ($orders as $phone => $quantities) {
            $items = [];

            foreach ($quantities as $variantId => $quantity) {
                $items[] = ['variant_id' => $variantId, 'quantity' => $quantity];
            }

            $pool->as($phone)
                ->path(base_path())
                ->timeout(120)
                ->env([
                    'APP_ENV' => 'testing',
                    'DB_CONNECTION' => 'mysql',
                    'DB_HOST' => $connection['host'],
                    'DB_PORT' => (string) $connection['port'],
                    'DB_DATABASE' => $connection['database'],
                    'DB_USERNAME' => $connection['username'],
                    'DB_PASSWORD' => $connection['password'],
                    'DB_URL' => '',
                    'CACHE_STORE' => 'array',
                    'QUEUE_CONNECTION' => 'sync',
                    'PLACE_ORDER' => json_encode(['phone' => $phone, 'items' => $items, 'start_at' => $startAt]),
                ])
                ->command([PHP_BINARY, base_path('tests/Support/place-order-process.php')]);
        }
    })->start()->wait();

    $decoded = [];

    foreach (array_keys($orders) as $phone) {
        $output = $results[$phone]->output();
        preg_match('/^RESULT:(.+)$/m', $output, $match);

        $decoded[$phone] = $match
            ? json_decode($match[1], true)
            : ['ok' => false, 'exception' => 'no result', 'message' => $output.$results[$phone]->errorOutput()];
    }

    return $decoded;
}

/**
 * @param  array<string, array<string, mixed>>  $results
 * @return array<int, string> everything that went wrong other than a stock error on a line
 */
function concurrencyUnexpected(array $results): array
{
    return collect($results)
        ->reject(fn (array $result) => $result['ok'] || isset($result['errors']))
        ->map(fn (array $result) => ($result['exception'] ?? '?').': '.($result['message'] ?? ''))
        ->values()
        ->all();
}

describe('the last unit', function () {
    it('goes to exactly one of five simultaneous orders', function () {
        $variant = concurrencyVariant(1);
        $phones = ['01711111111', '01722222222', '01733333333', '01744444444', '01755555555'];

        $results = concurrencyRun(array_fill_keys($phones, [$variant->id => 1]));

        $succeeded = collect($results)->where('ok', true);
        $failed = collect($results)->where('ok', false);

        expect(concurrencyUnexpected($results))->toBe([])
            ->and($succeeded)->toHaveCount(1)
            ->and($failed)->toHaveCount(4)
            ->and($failed->every(fn (array $result) => array_keys($result['errors']) === ['items.0.quantity']))->toBeTrue()
            ->and(Order::count())->toBe(1)
            ->and(Order::sole()->number)->toBe($succeeded->first()['number'])
            ->and($variant->fresh()->stock)->toBe(0)
            ->and(StockMovement::where('reason', StockMovementReason::OrderPlaced)->count())->toBe(1)
            ->and(StockMovement::where('product_variant_id', $variant->id)->sum('quantity'))->toBe(0);
    });

    it('sells every unit and no more when stock is 3 and eight orders arrive together', function () {
        $variant = concurrencyVariant(3);
        $phones = array_map(fn (int $i) => '0171000000'.$i, range(1, 8));

        $results = concurrencyRun(array_fill_keys($phones, [$variant->id => 1]));

        expect(concurrencyUnexpected($results))->toBe([])
            ->and(collect($results)->where('ok', true))->toHaveCount(3)
            ->and(Order::count())->toBe(3)
            ->and($variant->fresh()->stock)->toBe(0)
            ->and(StockMovement::where('reason', StockMovementReason::OrderPlaced)->count())->toBe(3);
    });
});

describe('order numbers', function () {
    it('stay unique and gapless when six orders for different products are placed together', function () {
        $phones = array_map(fn (int $i) => '0172000000'.$i, range(1, 6));
        $orders = [];

        foreach ($phones as $phone) {
            $orders[$phone] = [concurrencyVariant(5)->id => 1];
        }

        $results = concurrencyRun($orders);

        $suffixes = Order::pluck('number')->map(fn (string $number) => (int) substr($number, -4))->sort()->values()->all();

        expect(concurrencyUnexpected($results))->toBe([])
            ->and(collect($results)->where('ok', true))->toHaveCount(6)
            ->and($suffixes)->toBe([1, 2, 3, 4, 5, 6]);
    });
});

describe('orders for the same two variants in opposite order', function () {
    it('never deadlock and exactly one wins', function () {
        $a = concurrencyVariant(1);
        $b = concurrencyVariant(1);

        $results = concurrencyRun([
            '01711111111' => [$a->id => 1, $b->id => 1],
            '01722222222' => [$b->id => 1, $a->id => 1],
        ]);

        expect(concurrencyUnexpected($results))->toBe([])
            ->and(collect($results)->where('ok', true))->toHaveCount(1)
            ->and(collect($results)->where('ok', false))->toHaveCount(1)
            ->and(Order::count())->toBe(1)
            ->and($a->fresh()->stock)->toBe(0)
            ->and($b->fresh()->stock)->toBe(0)
            ->and(StockMovement::where('reason', StockMovementReason::OrderPlaced)->count())->toBe(2);
    });
});

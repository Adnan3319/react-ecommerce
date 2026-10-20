<?php

/**
 * One checkout in its own PHP process, for the real-MySQL concurrency tests (tests/Mysql).
 *
 * Reads the order from the PLACE_ORDER environment variable (JSON), waits until `start_at` so all
 * processes of a test hit the database together, calls CheckoutService::place() and prints one
 * line "RESULT:{json}". Database settings come from the environment of the parent test.
 */

use App\Enums\OrderSource;
use App\Services\Order\CheckoutData;
use App\Services\Order\CheckoutService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$request = json_decode((string) getenv('PLACE_ORDER'), true, flags: JSON_THROW_ON_ERROR);

$wait = $request['start_at'] - microtime(true);

if ($wait > 0) {
    usleep((int) ($wait * 1_000_000));
}

try {
    $order = $app->make(CheckoutService::class)->place(new CheckoutData(
        items: $request['items'],
        name: 'Concurrent '.$request['phone'],
        phone: $request['phone'],
        address: 'House 1, Road 2, Dhaka',
        deliveryZone: 'inside_dhaka',
        idempotencyKey: $request['phone'].'-'.$request['start_at'],
        source: OrderSource::Cart,
    ));

    $result = ['ok' => true, 'number' => $order->number];
} catch (ValidationException $e) {
    $result = ['ok' => false, 'errors' => $e->errors()];
} catch (Throwable $e) {
    $result = ['ok' => false, 'exception' => $e::class, 'message' => $e->getMessage()];
}

echo "\nRESULT:".json_encode($result)."\n";

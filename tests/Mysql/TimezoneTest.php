<?php

use App\Models\Order;
use App\Services\Dashboard\DashboardService;
use App\Services\Order\OrderNumberGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/*
 * Order numbers, the sequence day and the dashboard day buckets must agree with the application
 * timezone (Asia/Dhaka) on real MySQL, where DATE() depends on the connection's session time zone.
 */

beforeEach(function () {
    // 00:30 on the 21st in Dhaka is still the 20th in UTC.
    $this->travelTo(Carbon::parse('2026-10-21 00:30:00', 'Asia/Dhaka'));
});

it('runs the MySQL session in the Dhaka offset', function () {
    expect(DB::selectOne('SELECT @@session.time_zone AS zone')->zone)->toBe('+06:00');
});

it('gives an order placed at 00:30 the new day in its number and sequence', function () {
    $number = app(OrderNumberGenerator::class)->next();

    expect($number)->toBe('CK-261021-0001')
        ->and(DB::table('order_number_sequences')->pluck('day')->all())->toBe(['261021']);
});

it('puts an order placed at 00:30 into the bucket of the new day', function () {
    Order::factory()->create();

    $trend = app(DashboardService::class)->summary()['trend'];

    expect(DB::table('orders')->selectRaw('DATE(created_at) AS day')->value('day'))->toBe('2026-10-21')
        ->and(collect($trend)->last())->toBe(['date' => '2026-10-21', 'orders' => 1])
        ->and(collect($trend)->firstWhere('date', '2026-10-20')['orders'])->toBe(0);
});

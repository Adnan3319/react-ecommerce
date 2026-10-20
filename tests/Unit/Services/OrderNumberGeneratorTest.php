<?php

use App\Models\Order;
use App\Services\Order\OrderNumberGenerator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->generator = new OrderNumberGenerator;
    $this->travelTo(Carbon::parse('2026-03-05 10:00:00'));
});

describe('next', function () {
    it('starts a day at 0001 with the configured prefix', function () {
        expect($this->generator->next())->toBe('CK-260305-0001');
    });

    it('counts up within a day', function () {
        Order::factory()->create(['number' => 'CK-260305-0001']);

        expect($this->generator->next())->toBe('CK-260305-0002');
    });

    it('follows the highest number of the day, not the count', function () {
        Order::factory()->create(['number' => 'CK-260305-0007']);

        expect($this->generator->next())->toBe('CK-260305-0008');
    });

    it('restarts the sequence on the next day', function () {
        Order::factory()->create(['number' => 'CK-260305-0003']);
        $this->travelTo(Carbon::parse('2026-03-06 00:05:00'));

        expect($this->generator->next())->toBe('CK-260306-0001');
    });

    it('keeps counting past 9999', function () {
        Order::factory()->create(['number' => 'CK-260305-9999']);

        expect($this->generator->next())->toBe('CK-260305-10000');
    });

    it('uses the prefix from config', function () {
        config(['shop.order_number_prefix' => 'ZZ']);

        expect($this->generator->next())->toBe('ZZ-260305-0001');
    });

    it('hands out a new number on every call, even before the order is saved', function () {
        expect([$this->generator->next(), $this->generator->next()])->toBe(['CK-260305-0001', 'CK-260305-0002']);
    });

    it('gives a number back when the surrounding transaction rolls back', function () {
        try {
            DB::transaction(function () {
                $this->generator->next();

                throw new RuntimeException('order failed');
            });
        } catch (RuntimeException) {
        }

        expect($this->generator->next())->toBe('CK-260305-0001');
    });

    it('numbers for a given date', function () {
        expect($this->generator->next(Carbon::parse('2027-12-31')))->toBe('CK-271231-0001');
    });
});

describe('token', function () {
    it('is 32 random alphanumeric characters', function () {
        $token = $this->generator->token();

        expect($token)->toHaveLength(32)->toMatch('/^[A-Za-z0-9]{32}$/')
            ->and($this->generator->token())->not->toBe($token);
    });
});

describe('createWithNumber', function () {
    it('passes the next number to the callback and returns its result', function () {
        $order = $this->generator->createWithNumber(fn (string $number) => Order::factory()->create(['number' => $number]));

        expect($order->number)->toBe('CK-260305-0001');
    });

    it('retries with the following number when another request took it first', function () {
        $tried = [];

        $order = $this->generator->createWithNumber(function (string $number) use (&$tried) {
            $tried[] = $number;

            // A concurrent request commits the number this one just read.
            if (count($tried) === 1) {
                Order::factory()->create(['number' => $number]);
            }

            return Order::factory()->create(['number' => $number]);
        });

        expect($tried)->toBe(['CK-260305-0001', 'CK-260305-0002'])
            ->and($order->number)->toBe('CK-260305-0002');
    });

    it('gives up after five attempts', function () {
        $attempts = 0;

        try {
            $this->generator->createWithNumber(function (string $number) use (&$attempts) {
                $attempts++;
                Order::factory()->create(['number' => $number]);

                return Order::factory()->create(['number' => $number]);
            });
        } catch (UniqueConstraintViolationException) {
        }

        expect($attempts)->toBe(5);
    });

    it('does not retry other unique violations', function () {
        $existing = Order::factory()->create();
        $attempts = 0;

        $this->generator->createWithNumber(function (string $number) use ($existing, &$attempts) {
            $attempts++;

            return Order::factory()->create(['number' => $number, 'idempotency_key' => $existing->idempotency_key]);
        });
    })->throws(UniqueConstraintViolationException::class);
});

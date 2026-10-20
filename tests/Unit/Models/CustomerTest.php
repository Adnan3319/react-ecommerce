<?php

use App\Models\Customer;
use App\Models\Order;
use App\Support\Phone;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

describe('byPhone scope', function () {
    it('finds a customer by the normalized phone', function () {
        $customer = Customer::factory()->create(['phone' => '01712345678']);
        Customer::factory()->create();

        $found = Customer::byPhone(Phone::normalize('+880 1712-345678'))->get();

        expect($found->pluck('id')->all())->toBe([$customer->id]);
    });
});

describe('defaults', function () {
    it('is not blocked and has zero counters', function () {
        $customer = Customer::factory()->create()->fresh();

        expect($customer->is_blocked)->toBeFalse()
            ->and([$customer->orders_count, $customer->delivered_count, $customer->cancelled_count])->toBe([0, 0, 0]);
    });

    it('can be created blocked', function () {
        expect(Customer::factory()->blocked()->create()->is_blocked)->toBeTrue();
    });
});

describe('relations and deletes', function () {
    it('has orders', function () {
        $customer = Customer::factory()->has(Order::factory()->count(2))->create();

        expect($customer->orders)->toHaveCount(2);
    });

    it('is soft deleted and keeps its phone reserved', function () {
        $customer = Customer::factory()->create(['phone' => '01712345678']);
        $customer->delete();

        expect(Customer::byPhone('01712345678')->count())->toBe(0)
            ->and(Customer::withTrashed()->byPhone('01712345678')->count())->toBe(1);
    });

    it('cannot be hard deleted while it has orders', function () {
        $customer = Customer::factory()->has(Order::factory())->create();

        $customer->forceDelete();
    })->throws(QueryException::class);

    it('refuses a duplicate phone', function () {
        Customer::factory()->create(['phone' => '01712345678']);

        Customer::factory()->create(['phone' => '01712345678']);
    })->throws(UniqueConstraintViolationException::class);
});

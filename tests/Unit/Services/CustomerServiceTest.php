<?php

use App\Models\Customer;
use App\Services\Order\CustomerService;

beforeEach(function () {
    $this->customers = new CustomerService;
});

describe('block', function () {
    it('blocks an existing customer found by any phone format', function () {
        $customer = Customer::factory()->create(['phone' => '01712345678']);

        $this->customers->block('+880 1712-345678');

        expect($customer->fresh()->is_blocked)->toBeTrue();
    });

    it('blocks a phone that never ordered in advance', function () {
        $this->customers->block('01812345678');

        $customer = Customer::sole();

        expect($customer->phone)->toBe('01812345678')->and($customer->is_blocked)->toBeTrue();
    });

    it('is safe to repeat', function () {
        $this->customers->block('01712345678');
        $this->customers->block('01712345678');

        expect(Customer::count())->toBe(1);
    });

    it('rejects an invalid phone', function () {
        $this->customers->block('12345');
    })->throws(InvalidArgumentException::class);
});

describe('unblock', function () {
    it('lets a blocked phone order again', function () {
        $customer = Customer::factory()->blocked()->create(['phone' => '01712345678']);

        $this->customers->unblock('01712345678');

        expect($customer->fresh()->is_blocked)->toBeFalse();
    });

    it('rejects an invalid phone', function () {
        $this->customers->unblock('nope');
    })->throws(InvalidArgumentException::class);
});

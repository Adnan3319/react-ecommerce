<?php

namespace App\Services\Order;

use App\Models\Customer;
use App\Support\Phone;
use InvalidArgumentException;

class CustomerService
{
    /**
     * Blocks a phone from ordering. A phone that never ordered gets a placeholder customer,
     * so it can be blocked in advance.
     *
     * @throws InvalidArgumentException when the phone is not a valid Bangladeshi mobile number
     */
    public function block(string $phone): Customer
    {
        return $this->setBlocked($phone, true);
    }

    /**
     * @throws InvalidArgumentException when the phone is not a valid Bangladeshi mobile number
     */
    public function unblock(string $phone): Customer
    {
        return $this->setBlocked($phone, false);
    }

    private function setBlocked(string $phone, bool $blocked): Customer
    {
        $normalized = Phone::normalize($phone) ?? throw new InvalidArgumentException("Not a valid phone number: [{$phone}].");

        $customer = Customer::withTrashed()->firstOrCreate(['phone' => $normalized], ['name' => $normalized]);
        $customer->update(['is_blocked' => $blocked]);

        return $customer;
    }
}

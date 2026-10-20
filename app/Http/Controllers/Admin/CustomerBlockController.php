<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Order\CustomerService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CustomerBlockController extends Controller
{
    public function __construct(private readonly CustomerService $customers) {}

    public function store(Customer $customer): RedirectResponse
    {
        $this->customers->block($customer->phone);

        Inertia::flash('success', 'Phone blocked.');

        return back();
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->customers->unblock($customer->phone);

        Inertia::flash('success', 'Phone unblocked.');

        return back();
    }
}

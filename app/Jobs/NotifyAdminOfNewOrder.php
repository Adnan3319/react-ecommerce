<?php

namespace App\Jobs;

use App\Mail\NewOrderMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Emails the admin about a new order. Dispatched after the order is committed and run by the
 * queue worker, so the order request never waits for the mail server. Nothing is sent (and
 * nothing is logged) when no admin address is configured.
 */
class NotifyAdminOfNewOrder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $orderId)
    {
        $this->afterCommit();
    }

    /**
     * Seconds to wait before the second and the third attempt.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        $email = config('shop.admin_order_email');

        if (blank($email)) {
            return;
        }

        $order = Order::query()->with('items')->find($this->orderId);

        if ($order !== null) {
            Mail::to($email)->send(new NewOrderMail($order));
        }
    }
}

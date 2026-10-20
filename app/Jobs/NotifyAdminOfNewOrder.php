<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Emails the admin about a new order. Dispatched after the order is committed; the mail itself
 * is added in step 06 (doc/order/06-notifications.md).
 */
class NotifyAdminOfNewOrder implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $orderId)
    {
        $this->afterCommit();
    }

    public function handle(): void
    {
        // Implemented in step 06.
    }
}

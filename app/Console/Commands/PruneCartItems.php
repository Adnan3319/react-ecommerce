<?php

namespace App\Console\Commands;

use App\Models\CartItem;
use Illuminate\Console\Command;

class PruneCartItems extends Command
{
    /** Same as the lifetime of the cart cookie, so a cart that nobody can open any more is removed. */
    private const STALE_AFTER_DAYS = 30;

    protected $signature = 'cart:prune';

    protected $description = 'Delete cart items that were not touched for 30 days';

    public function handle(): int
    {
        $deleted = CartItem::query()->where('updated_at', '<', now()->subDays(self::STALE_AFTER_DAYS))->delete();

        $this->info("Deleted {$deleted} stale cart items.");

        return self::SUCCESS;
    }
}

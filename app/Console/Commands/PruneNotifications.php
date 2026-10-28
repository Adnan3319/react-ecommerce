<?php

namespace App\Console\Commands;

use App\Services\Notification\NotificationService;
use Illuminate\Console\Command;

class PruneNotifications extends Command
{
    protected $signature = 'notifications:prune';

    protected $description = 'Delete read admin notifications older than 30 days';

    public function handle(NotificationService $notifications): int
    {
        $deleted = $notifications->prune();

        $this->info("Deleted {$deleted} read notifications.");

        return self::SUCCESS;
    }
}

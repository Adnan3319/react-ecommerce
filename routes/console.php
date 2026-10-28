<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('cart:prune')->daily();
Schedule::command('notifications:prune')->daily();

// Shared hosting has no queue daemon: the cron entry runs schedule:run every minute, which drains the queue.
Schedule::command('queue:work --stop-when-empty --tries=3')->everyMinute()->withoutOverlapping();

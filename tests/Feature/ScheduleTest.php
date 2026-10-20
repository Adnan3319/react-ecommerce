<?php

use Illuminate\Support\Facades\Artisan;

/**
 * What `schedule:list` shows: shared hosting runs `schedule:run` from cron every minute, so anything
 * that must happen in the background has to be scheduled.
 */
function scheduledCommands(): string
{
    Artisan::call('schedule:list');

    return Artisan::output();
}

it('drains the queue every minute so queued mail goes out without a daemon', function () {
    expect(scheduledCommands())->toMatch('/\*\s+\*\s+\*\s+\*\s+\*\s+.*queue:work --stop-when-empty --tries=3/');
});

it('prunes stale carts daily', function () {
    expect(scheduledCommands())->toContain('cart:prune');
});

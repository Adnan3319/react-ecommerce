<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\Cache;

/**
 * The cached dashboard summary. Anything that changes an order or stock forgets it.
 */
class DashboardCache
{
    public const KEY = 'dashboard.summary';

    public const TTL_SECONDS = 300;

    public function forget(): void
    {
        Cache::forget(self::KEY);
    }
}

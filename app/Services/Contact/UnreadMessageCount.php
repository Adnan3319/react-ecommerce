<?php

namespace App\Services\Contact;

use App\Models\ContactMessage;
use Illuminate\Support\Facades\Cache;

/**
 * Unread contact messages for the admin sidebar badge. Cached for a minute and forgotten whenever a
 * message arrives, is read or is deleted, so the badge is never stale after an action.
 */
class UnreadMessageCount
{
    private const CACHE_KEY = 'messages.unread_count';

    private const TTL_SECONDS = 60;

    public function get(): int
    {
        return Cache::remember(
            self::CACHE_KEY,
            self::TTL_SECONDS,
            fn (): int => ContactMessage::query()->unread()->count(),
        );
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}

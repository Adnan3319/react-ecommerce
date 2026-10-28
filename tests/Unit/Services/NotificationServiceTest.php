<?php

use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\DatabaseNotification;

beforeEach(function () {
    $this->service = new NotificationService;
});

/**
 * @param  array<string, mixed>  $attributes
 */
function storedNotification(User $user, array $attributes = []): DatabaseNotification
{
    return $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\Notifications\NewOrderNotification',
        'data' => ['type' => 'order', 'title' => 'New order', 'body' => 'x', 'url' => '/admin/orders/1', 'reference_id' => 1],
        ...$attributes,
    ]);
}

describe('prune', function () {
    it('deletes only read notifications older than 30 days', function () {
        $user = User::factory()->create();
        $oldRead = storedNotification($user, ['read_at' => now()->subDays(40), 'created_at' => now()->subDays(41)]);
        $oldUnread = storedNotification($user, ['created_at' => now()->subDays(41)]);
        $recentRead = storedNotification($user, ['read_at' => now()->subDay(), 'created_at' => now()->subDays(29)]);

        $deleted = $this->service->prune();

        expect($deleted)->toBe(1)
            ->and(DatabaseNotification::find($oldRead->id))->toBeNull()
            ->and(DatabaseNotification::find($oldUnread->id))->not->toBeNull()
            ->and(DatabaseNotification::find($recentRead->id))->not->toBeNull();
    });

    it('keeps a notification that is exactly 30 days old', function () {
        $this->freezeTime();
        $user = User::factory()->create();
        $notification = storedNotification($user, ['read_at' => now(), 'created_at' => now()->subDays(30)]);

        $this->service->prune();

        expect(DatabaseNotification::find($notification->id))->not->toBeNull();
    });
});

describe('scoping', function () {
    it('counts only the given user\'s unread notifications', function () {
        $user = User::factory()->create();
        storedNotification($user);
        storedNotification($user, ['read_at' => now()]);
        storedNotification(User::factory()->create());

        expect($this->service->unreadCount($user))->toBe(1);
    });

    it('cannot mark another user\'s notification as read', function () {
        $owner = User::factory()->create();
        $notification = storedNotification($owner);

        expect(fn () => $this->service->markRead(User::factory()->create(), $notification->id))
            ->toThrow(ModelNotFoundException::class);
        expect($notification->fresh()->read_at)->toBeNull();
    });

    it('returns at most ten, newest first', function () {
        $user = User::factory()->create();
        foreach (range(1, 12) as $minutesAgo) {
            storedNotification($user, ['created_at' => now()->subMinutes($minutesAgo)]);
        }

        $latest = $this->service->latest($user);

        expect($latest)->toHaveCount(10)
            ->and($latest->first()->created_at->gt($latest->last()->created_at))->toBeTrue();
    });
});

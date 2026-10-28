<?php

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Testing\AssertableInertia;

/**
 * @param  array<string, mixed>  $data
 */
function notify(User $admin, array $data = [], ?DateTimeInterface $readAt = null): DatabaseNotification
{
    return $admin->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\Notifications\NewOrderNotification',
        'data' => [
            'type' => 'order',
            'title' => 'New order CK-1',
            'body' => 'Nusrat · ৳1,720 · Inside City',
            'url' => '/admin/orders/1',
            'reference_id' => 1,
            ...$data,
        ],
        'read_at' => $readAt,
    ]);
}

describe('access', function () {
    it('redirects guests to the admin login', function () {
        $this->get(route('admin.notifications.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.notifications.read-all'))->assertRedirect(route('admin.login'));
    });
});

describe('shared props', function () {
    // The admin pages live in resources/js/admin, outside Inertia's default page path.
    beforeEach(fn () => config(['inertia.testing.ensure_pages_exist' => false]));

    it('sends the unread count of the signed-in admin only', function () {
        $admin = User::factory()->create();
        notify($admin);
        notify($admin);
        notify($admin, readAt: now());
        notify(User::factory()->create());

        actingAsAdmin($admin)->get(route('admin.messages.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('notifications.unreadCount', 2));
    });

    it('loads the bell items only when asked for', function () {
        $admin = User::factory()->create();
        notify($admin);

        actingAsAdmin($admin)->get(route('admin.messages.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->missing('notificationItems')
                ->reloadOnly('notificationItems', fn (AssertableInertia $reload) => $reload
                    ->has('notificationItems', 1)
                    ->where('notificationItems.0.title', 'New order CK-1')
                    ->where('notificationItems.0.read', false)));
    });

    it('sends the latest ten, newest first', function () {
        $admin = User::factory()->create();
        foreach (range(1, 12) as $i) {
            notify($admin, ['title' => "Order {$i}"])->forceFill(['created_at' => now()->subMinutes(20 - $i)])->save();
        }

        actingAsAdmin($admin)->get(route('admin.messages.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->reloadOnly('notificationItems', fn (AssertableInertia $reload) => $reload
                    ->has('notificationItems', 10)
                    ->where('notificationItems.0.title', 'Order 12')));
    });
});

describe('list', function () {
    it('filters by type and read state', function () {
        $admin = User::factory()->create();
        notify($admin, ['type' => 'order', 'title' => 'Unread order']);
        notify($admin, ['type' => 'message', 'title' => 'Unread message']);
        notify($admin, ['type' => 'order', 'title' => 'Read order'], now());

        actingAsAdmin($admin)->get(route('admin.notifications.index', ['type' => 'message']))
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Notifications/Index', false)
                ->has('feed.data', 1)->where('feed.data.0.title', 'Unread message'));

        $this->get(route('admin.notifications.index', ['status' => 'read']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('feed.data', 1)->where('feed.data.0.title', 'Read order'));

        $this->get(route('admin.notifications.index', ['type' => 'order', 'status' => 'unread']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('feed.data', 1)->where('feed.data.0.title', 'Unread order'));
    });

    it('does not show another admin\'s notifications', function () {
        notify(User::factory()->create());

        actingAsAdmin()->get(route('admin.notifications.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('feed.data', 0));
    });

    it('rejects an unknown filter', function () {
        actingAsAdmin()->get(route('admin.notifications.index', ['type' => 'bogus']))->assertSessionHasErrors('type');
    });
});

describe('mark read', function () {
    it('marks one as read and opens the page it points to', function () {
        $admin = User::factory()->create();
        $notification = notify($admin, ['url' => '/admin/orders/7']);

        actingAsAdmin($admin)->post(route('admin.notifications.read', $notification->id))->assertRedirect('/admin/orders/7');

        expect($notification->fresh()->read_at)->not->toBeNull();
    });

    it('never follows a url that leaves the admin area', function (string $url) {
        $admin = User::factory()->create();
        $notification = notify($admin, ['url' => $url]);

        actingAsAdmin($admin)->post(route('admin.notifications.read', $notification->id))
            ->assertRedirect(route('admin.notifications.index', absolute: false));
    })->with([
        'absolute url' => 'https://evil.example/admin/',
        'protocol-relative url' => '//evil.example/admin/',
        'storefront path' => '/products/x',
    ]);

    it('returns 404 for another admin\'s notification and leaves it unread', function () {
        $other = notify(User::factory()->create());

        actingAsAdmin()->post(route('admin.notifications.read', $other->id))->assertNotFound();

        expect($other->fresh()->read_at)->toBeNull();
    });

    it('marks all of the admin\'s notifications read and no one else\'s', function () {
        $admin = User::factory()->create();
        notify($admin);
        notify($admin);
        $other = notify(User::factory()->create());

        actingAsAdmin($admin)->post(route('admin.notifications.read-all'))->assertRedirect();

        expect($admin->unreadNotifications()->count())->toBe(0)
            ->and($other->fresh()->read_at)->toBeNull();
    });
});

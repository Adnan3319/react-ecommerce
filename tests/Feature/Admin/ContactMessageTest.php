<?php

use App\Models\ContactMessage;
use App\Models\Order;
use Inertia\Testing\AssertableInertia;

describe('access', function () {
    it('redirects guests to the admin login', function () {
        $this->get(route('admin.messages.index'))->assertRedirect(route('admin.login'));
    });

    it('shows the inbox to an admin', function () {
        actingAsAdmin()->get(route('admin.messages.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Messages/Index', false));
    });
});

describe('list', function () {
    it('puts unread messages first, then the newest', function () {
        $oldUnread = ContactMessage::factory()->create(['name' => 'Old unread', 'created_at' => now()->subDays(2)]);
        $newRead = ContactMessage::factory()->read()->create(['name' => 'New read', 'created_at' => now()->subHour()]);
        $newUnread = ContactMessage::factory()->create(['name' => 'New unread', 'created_at' => now()->subMinute()]);

        actingAsAdmin()->get(route('admin.messages.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('messages.data', 3)
                ->where('messages.data.0.id', $newUnread->id)
                ->where('messages.data.1.id', $oldUnread->id)
                ->where('messages.data.2.id', $newRead->id));
    });

    it('filters by read state', function () {
        ContactMessage::factory()->create(['name' => 'Unread one']);
        ContactMessage::factory()->read()->create(['name' => 'Read one']);

        actingAsAdmin()->get(route('admin.messages.index', ['read' => 'unread']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('messages.data', 1)->where('messages.data.0.name', 'Unread one'));

        actingAsAdmin()->get(route('admin.messages.index', ['read' => 'read']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('messages.data', 1)->where('messages.data.0.name', 'Read one'));
    });

    it('searches by name, mobile and email', function (string $term) {
        ContactMessage::factory()->create(['name' => 'Rahim Uddin', 'mobile' => '01812345678', 'email' => 'rahim@shop.test']);
        ContactMessage::factory()->create(['name' => 'Someone Else', 'mobile' => '01911111111']);

        actingAsAdmin()->get(route('admin.messages.index', ['search' => $term]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('messages.data', 1)->where('messages.data.0.name', 'Rahim Uddin'));
    })->with([
        'name' => 'Rahim',
        'mobile' => '0181234',
        'international mobile' => '+880 1812-34',
        'email' => 'rahim@shop',
    ]);
});

describe('detail', function () {
    it('marks the message as read when it is opened', function () {
        $message = ContactMessage::factory()->create();

        actingAsAdmin()->get(route('admin.messages.show', $message))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Messages/Show', false)
                ->where('message.id', $message->id)
                ->where('message.is_read', true));

        expect($message->fresh()->is_read)->toBeTrue();
    });

    it('links to the orders of that phone only when there are some', function () {
        $message = ContactMessage::factory()->create(['mobile' => '01712345678']);
        Order::factory()->create(['shipping_phone' => '01712345678']);

        actingAsAdmin()->get(route('admin.messages.show', $message))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('orders.count', 1)
                ->where('orders.url', route('admin.orders.index', ['search' => '01712345678'], absolute: false)));
    });

    it('has no orders link for a phone that never ordered', function () {
        $message = ContactMessage::factory()->create();

        actingAsAdmin()->get(route('admin.messages.show', $message))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('orders.count', 0)->where('orders.url', null));
    });

    it('returns 404 for a missing message', function () {
        actingAsAdmin()->get(route('admin.messages.show', 999999))->assertNotFound();
    });
});

describe('delete', function () {
    it('deletes a message', function () {
        $message = ContactMessage::factory()->create();

        actingAsAdmin()->delete(route('admin.messages.destroy', $message))->assertRedirect(route('admin.messages.index'));

        expect(ContactMessage::count())->toBe(0);
    });
});

describe('sidebar badge', function () {
    it('counts unread messages and drops after one is opened', function () {
        $messages = ContactMessage::factory()->count(2)->create();

        actingAsAdmin()->get(route('admin.messages.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('admin.unreadMessages', 2));

        $this->get(route('admin.messages.show', $messages->first()))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('admin.unreadMessages', 1));
    });
});

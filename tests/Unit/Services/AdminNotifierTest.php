<?php

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\User;
use App\Notifications\NewContactMessageNotification;
use App\Notifications\NewOrderNotification;
use App\Services\Notification\AdminNotifier;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->notifier = new AdminNotifier;
});

it('sends a new order notification to every active admin', function () {
    Notification::fake();
    $active = User::factory()->count(2)->create();
    $inactive = User::factory()->inactive()->create();

    $this->notifier->newOrder(Order::factory()->create());

    Notification::assertSentTo($active, NewOrderNotification::class);
    Notification::assertNotSentTo($inactive, NewOrderNotification::class);
});

it('sends a new contact message notification to every active admin', function () {
    Notification::fake();
    $admin = User::factory()->create();

    $this->notifier->newContactMessage(ContactMessage::factory()->create());

    Notification::assertSentTo($admin, NewContactMessageNotification::class);
});

it('reports and swallows a failure instead of throwing', function () {
    Exceptions::fake();
    User::factory()->create();
    Notification::shouldReceive('send')->andThrow(new RuntimeException('queue is down'));

    $this->notifier->newContactMessage(ContactMessage::factory()->create());

    Exceptions::assertReported(RuntimeException::class);
});

it('does nothing when there is no active admin', function () {
    Notification::fake();
    User::factory()->inactive()->create();

    $this->notifier->newOrder(Order::factory()->create());

    Notification::assertNothingSent();
});

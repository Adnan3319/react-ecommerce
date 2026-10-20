<?php

use App\Jobs\NotifyAdminOfNewOrder;
use App\Mail\NewOrderMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    config(['shop.admin_order_email' => 'owner@shop.test']);
});

describe('handle', function () {
    it('mails the order to the configured admin address', function () {
        $order = Order::factory()->withItems(2)->create();

        (new NotifyAdminOfNewOrder($order->id))->handle();

        Mail::assertSent(NewOrderMail::class, fn (NewOrderMail $mail) => $mail->hasTo('owner@shop.test') && $mail->order->is($order));
        Mail::assertSentCount(1);
    });

    it('sends nothing when no admin address is configured', function (?string $email) {
        config(['shop.admin_order_email' => $email]);
        $order = Order::factory()->create();

        (new NotifyAdminOfNewOrder($order->id))->handle();

        Mail::assertNothingSent();
    })->with([
        'null' => [null],
        'empty' => [''],
        'blank' => ['   '],
    ]);

    it('does nothing for an order that no longer exists', function () {
        (new NotifyAdminOfNewOrder(999))->handle();

        Mail::assertNothingSent();
    });
});

describe('queue settings', function () {
    it('is queued, tried 3 times and waits longer between attempts', function () {
        $job = new NotifyAdminOfNewOrder(1);

        expect($job)->toBeInstanceOf(Illuminate\Contracts\Queue\ShouldQueue::class)
            ->and($job->tries)->toBe(3)
            ->and($job->backoff())->toBe([60, 300]);
    });

    it('is only handed to the queue after the order is committed', function () {
        expect((new NotifyAdminOfNewOrder(1))->afterCommit)->toBeTrue();
    });
});

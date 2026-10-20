<?php

use App\Enums\OrderStatus;

/**
 * The transition map of doc/order/README.md §4, written out independently of the enum.
 *
 * @return array<string, array<int, string>>
 */
function orderTransitionMap(): array
{
    return [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'returned'],
        'delivered' => ['returned'],
        'cancelled' => [],
        'returned' => [],
    ];
}

/**
 * @return array<string, array{OrderStatus, OrderStatus}>
 */
function allowedOrderTransitions(): array
{
    $pairs = [];

    foreach (orderTransitionMap() as $from => $targets) {
        foreach ($targets as $to) {
            $pairs["{$from} → {$to}"] = [OrderStatus::from($from), OrderStatus::from($to)];
        }
    }

    return $pairs;
}

/**
 * Every pair that is not in the map, including a status to itself.
 *
 * @return array<string, array{OrderStatus, OrderStatus}>
 */
function forbiddenOrderTransitions(): array
{
    $pairs = [];

    foreach (OrderStatus::cases() as $from) {
        foreach (OrderStatus::cases() as $to) {
            if (! in_array($to->value, orderTransitionMap()[$from->value], true)) {
                $pairs["{$from->value} → {$to->value}"] = [$from, $to];
            }
        }
    }

    return $pairs;
}

describe('canTransitionTo', function () {
    it('allows every transition of the map', function (OrderStatus $from, OrderStatus $to) {
        expect($from->canTransitionTo($to))->toBeTrue();
    })->with(allowedOrderTransitions());

    it('rejects every other transition', function (OrderStatus $from, OrderStatus $to) {
        expect($from->canTransitionTo($to))->toBeFalse();
    })->with(forbiddenOrderTransitions());

    it('rejects the named mistakes', function (OrderStatus $from, OrderStatus $to) {
        expect($from->canTransitionTo($to))->toBeFalse();
    })->with([
        'delivered → pending' => [OrderStatus::Delivered, OrderStatus::Pending],
        'cancelled → confirmed' => [OrderStatus::Cancelled, OrderStatus::Confirmed],
        'shipped → cancelled' => [OrderStatus::Shipped, OrderStatus::Cancelled],
        'returned → delivered' => [OrderStatus::Returned, OrderStatus::Delivered],
    ]);

    it('allows returning an order that was shipped but never delivered', function () {
        expect(OrderStatus::Shipped->canTransitionTo(OrderStatus::Returned))->toBeTrue();
    });
});

describe('allowedNext', function () {
    it('lists the statuses of the map for each status', function (OrderStatus $status) {
        $next = array_map(fn (OrderStatus $target) => $target->value, $status->allowedNext());

        expect($next)->toBe(orderTransitionMap()[$status->value]);
    })->with(OrderStatus::cases());
});

describe('restoresStock', function () {
    it('is true only for cancelled and returned', function (OrderStatus $status, bool $restores) {
        expect($status->restoresStock())->toBe($restores);
    })->with([
        'pending' => [OrderStatus::Pending, false],
        'confirmed' => [OrderStatus::Confirmed, false],
        'processing' => [OrderStatus::Processing, false],
        'shipped' => [OrderStatus::Shipped, false],
        'delivered' => [OrderStatus::Delivered, false],
        'cancelled' => [OrderStatus::Cancelled, true],
        'returned' => [OrderStatus::Returned, true],
    ]);
});

describe('presentation', function () {
    it('has a label and a badge color for every status', function (OrderStatus $status) {
        expect($status->label())->not->toBeEmpty()
            ->and($status->color())->toBeIn(['primary', 'success', 'error', 'warning', 'info', 'light', 'dark']);
    })->with(OrderStatus::cases());
});

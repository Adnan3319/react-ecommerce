<?php

use App\Support\Money;

describe('toMinor', function () {
    it('converts taka to minor units', function (string|int|float $taka, int $minor) {
        expect(Money::toMinor($taka))->toBe($minor);
    })->with([
        'whole string' => ['1650', 165000],
        'one decimal' => ['1650.5', 165050],
        'two decimals' => ['1650.55', 165055],
        'thousands separator' => ['1,650.50', 165050],
        'integer' => [20, 2000],
        'float' => [19.99, 1999],
        'zero' => ['0', 0],
    ]);

    it('keeps null and empty as null', function () {
        expect(Money::toMinor(null))->toBeNull()->and(Money::toMinor(''))->toBeNull();
    });

    it('rejects anything that is not a plain amount', function (string $input) {
        Money::toMinor($input);
    })->with(['abc', '-5', '10.999', '1e3', '10.'])->throws(InvalidArgumentException::class);
});

describe('toTaka', function () {
    it('formats minor units for a form field', function () {
        expect(Money::toTaka(165000))->toBe('1650')
            ->and(Money::toTaka(165050))->toBe('1650.50')
            ->and(Money::toTaka(5))->toBe('0.05');
    });
});

describe('format', function () {
    it('formats minor units for display', function () {
        expect(Money::format(165000))->toBe('৳1,650')
            ->and(Money::format(165050))->toBe('৳1,650.50')
            ->and(Money::format(0))->toBe('৳0');
    });
});

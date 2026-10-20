<?php

use App\Support\Phone;

describe('normalize', function () {
    it('returns the 01XXXXXXXXX form of a valid number', function (string $input) {
        expect(Phone::normalize($input))->toBe('01712345678');
    })->with([
        'local' => '01712345678',
        'with +880' => '+8801712345678',
        'with 880' => '8801712345678',
        'spaces' => ' 017 1234 5678 ',
        'dashes' => '017-1234-5678',
        '+880 with separators' => '+880 1712-345678',
    ]);

    it('accepts every mobile operator prefix 013 to 019', function (string $prefix) {
        expect(Phone::normalize($prefix.'12345678'))->toBe($prefix.'12345678');
    })->with(['013', '014', '015', '016', '017', '018', '019']);

    it('returns null for anything else', function (string $input) {
        expect(Phone::normalize($input))->toBeNull();
    })->with([
        'empty' => '',
        'letters' => 'abcdefghijk',
        'too short' => '0171234567',
        'too long' => '017123456789',
        'wrong operator prefix 012' => '01212345678',
        'wrong operator prefix 011' => '01112345678',
        'landline' => '0221234567',
        'missing leading zero' => '1712345678',
        'foreign +91' => '+919876543210',
        'plus in the middle' => '017+12345678',
        '880 without the mobile prefix' => '88012345678',
    ]);
});

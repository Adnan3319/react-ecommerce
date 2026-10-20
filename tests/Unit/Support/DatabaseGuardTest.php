<?php

use Tests\Support\DatabaseGuard;

describe('safe connections', function () {
    it('allows a database', function (string $driver, ?string $database) {
        expect(DatabaseGuard::isSafe($driver, $database))->toBeTrue();
    })->with([
        'in-memory sqlite' => ['sqlite', ':memory:'],
        'mysql database ending in _test' => ['mysql', 'checkit_test'],
        'sqlite file ending in _test' => ['sqlite', 'database/checkit_test'],
    ]);
});

describe('unsafe connections', function () {
    it('refuses a database that could hold real data', function (string $driver, ?string $database) {
        expect(DatabaseGuard::isSafe($driver, $database))->toBeFalse();
    })->with([
        'dev mysql database' => ['mysql', 'checkit_db'],
        'other project database' => ['mysql', 'gbs_soft'],
        'name only contains test' => ['mysql', 'test_checkit'],
        'sqlite file' => ['sqlite', 'database/database.sqlite'],
        'no database name' => ['mysql', null],
    ]);

    it('aborts with a message naming the connection, the database and the fix', function () {
        expect(fn () => DatabaseGuard::assertSafe('mysql', 'mysql', 'checkit_db'))
            ->toThrow(RuntimeException::class, 'connection [mysql] uses mysql database [checkit_db]');

        expect(fn () => DatabaseGuard::assertSafe('mysql', 'mysql', 'checkit_db'))
            ->toThrow(RuntimeException::class, 'php artisan optimize:clear');
    });

    it('does nothing for a safe connection', function () {
        DatabaseGuard::assertSafe('sqlite', 'sqlite', ':memory:');

        expect(true)->toBeTrue();
    });
});

describe('test environment', function () {
    it('is configured by phpunit.xml with in-memory sqlite', function () {
        $xml = simplexml_load_file(dirname(__DIR__, 3).'/phpunit.xml');
        $env = [];

        foreach ($xml->php->env as $entry) {
            $env[(string) $entry['name']] = (string) $entry['value'];
        }

        expect($env)->toMatchArray([
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
        ]);
    });
});

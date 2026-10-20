<?php

namespace Tests\Support;

use RuntimeException;

/**
 * Tests use RefreshDatabase, which wipes the database it runs on. This decides whether a
 * connection is allowed to be wiped: in-memory SQLite or a database whose name ends with `_test`.
 */
final class DatabaseGuard
{
    public static function isSafe(string $driver, ?string $database): bool
    {
        if ($driver === 'sqlite' && $database === ':memory:') {
            return true;
        }

        return $database !== null && str_ends_with($database, '_test');
    }

    /**
     * @throws RuntimeException when the connection could hold real data
     */
    public static function assertSafe(string $connection, string $driver, ?string $database): void
    {
        if (self::isSafe($driver, $database)) {
            return;
        }

        $name = $database ?? 'unknown';

        throw new RuntimeException(
            "Refusing to run tests: connection [{$connection}] uses {$driver} database [{$name}], which could hold real data. "
            .'Tests must use sqlite :memory: or a database ending in _test (see phpunit.xml). '
            .'A cached config is the usual cause: run `php artisan optimize:clear` and try again.'
        );
    }
}

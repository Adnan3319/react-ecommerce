<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\DatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    /**
     * Runs before the test traits (RefreshDatabase), so a wrong connection aborts the
     * suite before anything is migrated or wiped.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $connection = (string) config('database.default');

        DatabaseGuard::assertSafe(
            $connection,
            (string) config("database.connections.{$connection}.driver"),
            config("database.connections.{$connection}.database"),
        );
    }
}

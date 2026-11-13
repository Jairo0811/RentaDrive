<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TestDatabaseDriverTest extends TestCase
{
    /**
     * Evita que la suite de CI use por error otro motor distinto al esperado.
     * El workflow define EXPECT_DB_DRIVER=sqlsrv; en local, sin esa variable, se espera SQLite.
     */
    public function test_suite_runs_against_the_expected_database_driver(): void
    {
        $expected = getenv('EXPECT_DB_DRIVER') ?: 'sqlite';

        $this->assertSame($expected, DB::connection()->getDriverName());
    }
}

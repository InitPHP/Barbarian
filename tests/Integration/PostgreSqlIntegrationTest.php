<?php

declare(strict_types=1);

namespace Test\InitPHP\Barbarian\Integration;

use PDO;

/**
 * Runs the {@see DriverTestCase} lifecycle against PostgreSQL.
 *
 * Configure with the BARBARIAN_PGSQL_DSN, BARBARIAN_PGSQL_USER and
 * BARBARIAN_PGSQL_PASSWORD environment variables.
 */
final class PostgreSqlIntegrationTest extends DriverTestCase
{
    protected function makePdo(): ?PDO
    {
        return self::pdoFromEnv('BARBARIAN_PGSQL');
    }
}

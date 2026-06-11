<?php

declare(strict_types=1);

namespace Test\InitPHP\Barbarian\Integration;

use PDO;

/**
 * Runs the {@see DriverTestCase} lifecycle against MySQL/MariaDB.
 *
 * Configure with the BARBARIAN_MYSQL_DSN, BARBARIAN_MYSQL_USER and
 * BARBARIAN_MYSQL_PASSWORD environment variables.
 */
final class MySqlIntegrationTest extends DriverTestCase
{
    protected function makePdo(): ?PDO
    {
        return self::pdoFromEnv('BARBARIAN_MYSQL');
    }
}

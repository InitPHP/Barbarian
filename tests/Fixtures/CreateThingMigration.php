<?php

declare(strict_types=1);

namespace Test\InitPHP\Barbarian\Fixtures;

use InitPHP\Barbarian\MigrationAbstract;
use InitPHP\Barbarian\QueryInterface;

/**
 * A migration whose up() creates a `things` table and whose down() drops it.
 */
final class CreateThingMigration extends MigrationAbstract
{
    public function up(QueryInterface $query): bool
    {
        $query->query('CREATE TABLE IF NOT EXISTS things (id INTEGER PRIMARY KEY, name TEXT)');

        return true;
    }

    public function down(QueryInterface $query): bool
    {
        $query->query('DROP TABLE IF EXISTS things');

        return true;
    }
}

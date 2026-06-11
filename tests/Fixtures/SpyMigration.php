<?php

declare(strict_types=1);

namespace Test\InitPHP\Barbarian\Fixtures;

use InitPHP\Barbarian\MigrationAbstract;
use InitPHP\Barbarian\QueryInterface;

/**
 * A migration that counts how many times up() and down() were invoked, so the
 * tests can assert the runner skips already-applied migrations.
 */
final class SpyMigration extends MigrationAbstract
{
    public int $upCalls = 0;

    public int $downCalls = 0;

    public function up(QueryInterface $query): bool
    {
        $this->upCalls++;

        return true;
    }

    public function down(QueryInterface $query): bool
    {
        $this->downCalls++;

        return true;
    }
}

<?php

declare(strict_types=1);

namespace Test\InitPHP\Barbarian\Fixtures;

use InitPHP\Barbarian\MigrationAbstract;
use InitPHP\Barbarian\QueryInterface;

/**
 * A migration that always reports failure, used to assert that the runner does
 * not record a state transition when up()/down() returns false.
 */
final class AlwaysFalseMigration extends MigrationAbstract
{
    public function up(QueryInterface $query): bool
    {
        return false;
    }

    public function down(QueryInterface $query): bool
    {
        return false;
    }
}

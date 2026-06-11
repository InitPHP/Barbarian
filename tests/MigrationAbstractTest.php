<?php

declare(strict_types=1);

namespace Test\InitPHP\Barbarian;

use InitPHP\Barbarian\MigrationAbstract;
use InitPHP\Barbarian\QueryInterface;
use PHPUnit\Framework\TestCase;
use Test\InitPHP\Barbarian\Fixtures\CreateThingMigration;

final class MigrationAbstractTest extends TestCase
{
    public function testGetNameStripsNamespace(): void
    {
        $migration = new CreateThingMigration();

        $this->assertSame('CreateThingMigration', $migration->getName());
    }

    public function testGetNameForGlobalClass(): void
    {
        $migration = new class extends MigrationAbstract {
            public function up(QueryInterface $query): bool
            {
                return true;
            }

            public function down(QueryInterface $query): bool
            {
                return true;
            }
        };

        $class = $migration::class;
        $position = strrpos($class, '\\');
        $expected = $position === false ? $class : substr($class, $position + 1);

        $this->assertSame($expected, $migration->getName());
    }
}

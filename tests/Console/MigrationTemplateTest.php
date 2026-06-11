<?php

declare(strict_types=1);

namespace Test\InitPHP\Barbarian\Console;

use InitPHP\Barbarian\Console\MigrationTemplate;
use InitPHP\Barbarian\MigrationInterface;
use PHPUnit\Framework\TestCase;
use Test\InitPHP\Barbarian\Support\Workspace;

use function bin2hex;
use function is_subclass_of;
use function random_bytes;

final class MigrationTemplateTest extends TestCase
{
    public function testRendersWithoutNamespace(): void
    {
        $source = MigrationTemplate::render('Migration_20240101000000', null);

        $this->assertStringContainsString('class Migration_20240101000000 extends MigrationAbstract', $source);
        $this->assertStringContainsString('public function up(QueryInterface $query): bool', $source);
        $this->assertStringContainsString('public function down(QueryInterface $query): bool', $source);
        $this->assertStringNotContainsString('namespace ', $source);
    }

    public function testRendersWithNamespace(): void
    {
        $source = MigrationTemplate::render('Migration_20240101000000', 'App\\Migrations');

        $this->assertStringContainsString('namespace App\\Migrations;', $source);
    }

    public function testRenderedSourceIsLoadablePhp(): void
    {
        $namespace = Workspace::uniqueNamespace();
        $name = 'Migration_' . bin2hex(random_bytes(5));
        $source = MigrationTemplate::render($name, $namespace);

        $dir = Workspace::make();
        $path = Workspace::writeFile($dir, $name . '.php', $source);
        require $path;

        $fqcn = $namespace . '\\' . $name;
        $this->assertTrue(is_subclass_of($fqcn, MigrationInterface::class));
    }
}

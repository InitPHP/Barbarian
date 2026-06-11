<?php

declare(strict_types=1);

namespace Test\InitPHP\Barbarian\Console;

use InitPHP\Barbarian\Console\ConfigLoader;
use InitPHP\Barbarian\MigrationException;
use PHPUnit\Framework\TestCase;
use Test\InitPHP\Barbarian\Support\Workspace;

use function json_encode;

final class ConfigLoaderTest extends TestCase
{
    public function testFromJsonParsesAllFields(): void
    {
        $config = ConfigLoader::fromJson((string) json_encode([
            'dsn'        => 'sqlite::memory:',
            'username'   => 'root',
            'password'   => 'secret',
            'folder'     => '/migrations',
            'table_name' => 'versions',
            'namespace'  => 'App\\Migrations',
        ]));

        $this->assertSame('sqlite::memory:', $config->dsn);
        $this->assertSame('root', $config->username);
        $this->assertSame('secret', $config->password);
        $this->assertSame('/migrations', $config->folder);
        $this->assertSame('versions', $config->migrationTable);
        $this->assertSame('App\\Migrations', $config->namespace);
    }

    public function testTableNameKeyMapsToMigrationTable(): void
    {
        // Regression: the CLI config uses "table_name" but the library option
        // is "migrationTable"; the loader must bridge the two.
        $config = ConfigLoader::fromJson((string) json_encode([
            'dsn'        => 'sqlite::memory:',
            'username'   => '',
            'password'   => '',
            'folder'     => '/migrations',
            'table_name' => 'my_versions',
        ]));

        $this->assertSame('my_versions', $config->migrationTable);
    }

    public function testMigrationTableKeyIsAlsoAccepted(): void
    {
        $config = ConfigLoader::fromJson((string) json_encode([
            'dsn'            => 'sqlite::memory:',
            'username'       => '',
            'password'       => '',
            'folder'         => '/migrations',
            'migrationTable' => 'native_versions',
        ]));

        $this->assertSame('native_versions', $config->migrationTable);
    }

    public function testDefaultsAreApplied(): void
    {
        $config = ConfigLoader::fromJson((string) json_encode([
            'dsn'      => 'sqlite::memory:',
            'username' => '',
            'password' => '',
            'folder'   => '/migrations',
        ]));

        $this->assertSame('migrations_versions', $config->migrationTable);
        $this->assertNull($config->namespace);
    }

    public function testEmptyNamespaceBecomesNull(): void
    {
        $config = ConfigLoader::fromJson((string) json_encode([
            'dsn'       => 'sqlite::memory:',
            'username'  => '',
            'password'  => '',
            'folder'    => '/migrations',
            'namespace' => '',
        ]));

        $this->assertNull($config->namespace);
    }

    public function testMissingRequiredKeyThrows(): void
    {
        $this->expectException(MigrationException::class);

        ConfigLoader::fromJson((string) json_encode([
            'username' => '',
            'password' => '',
            'folder'   => '/migrations',
        ]));
    }

    public function testInvalidJsonThrows(): void
    {
        // Regression: invalid JSON returns null from json_decode (not false),
        // and must be reported rather than slipping through.
        $this->expectException(MigrationException::class);

        ConfigLoader::fromJson('this is not json');
    }

    public function testFromFileReadsConfig(): void
    {
        $dir = Workspace::make();
        $path = Workspace::writeFile($dir, 'barbarian.json', (string) json_encode([
            'dsn'      => 'sqlite::memory:',
            'username' => '',
            'password' => '',
            'folder'   => $dir,
        ]));

        $config = ConfigLoader::fromFile($path);

        $this->assertSame($dir, $config->folder);
    }

    public function testFromFileMissingThrows(): void
    {
        $this->expectException(MigrationException::class);

        ConfigLoader::fromFile('/no/such/barbarian.json');
    }
}

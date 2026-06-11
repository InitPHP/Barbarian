<?php

declare(strict_types=1);

namespace Test\InitPHP\Barbarian\Console;

use InitPHP\Barbarian\Console\CreateCommand;
use InitPHP\Barbarian\Console\DownCommand;
use InitPHP\Barbarian\Console\InitCommand;
use InitPHP\Barbarian\Console\StatusCommand;
use InitPHP\Barbarian\Console\UpCommand;
use InitPHP\Console\Application;
use PDO;
use PHPUnit\Framework\TestCase;
use Test\InitPHP\Barbarian\Support\MemoryOutput;
use Test\InitPHP\Barbarian\Support\Workspace;

use function array_merge;
use function file_get_contents;
use function glob;
use function json_decode;
use function json_encode;
use function mkdir;
use function strpos;

final class CommandTest extends TestCase
{
    /**
     * @param array<int, string> $args
     */
    private function dispatch(array $args, MemoryOutput $output): void
    {
        $app = new Application('Barbarian', 'test', $output);
        $app->register(InitCommand::class);
        $app->register(CreateCommand::class);
        $app->register(StatusCommand::class);
        $app->register(UpCommand::class);
        $app->register(DownCommand::class);
        // argv[0] is the script name, which Application::run() discards.
        $app->run(array_merge(['barbarian'], $args));
    }

    /**
     * @return array{0: string, 1: string, 2: string} [migrationDir, configPath, dbFile]
     */
    private function makeProject(?string $namespace = null, ?string $dsn = null): array
    {
        $dir = Workspace::make();
        $migrationDir = $dir . DIRECTORY_SEPARATOR . 'migrations';
        mkdir($migrationDir);
        $dbFile = $dir . DIRECTORY_SEPARATOR . 'db.sqlite';

        $configPath = Workspace::writeFile($dir, 'barbarian.json', (string) json_encode([
            'dsn'        => $dsn ?? ('sqlite:' . $dbFile),
            'username'   => '',
            'password'   => '',
            'folder'     => $migrationDir,
            'table_name' => 'migrations_versions',
            'namespace'  => $namespace,
        ]));

        return [$migrationDir, $configPath, $dbFile];
    }

    private function tableExists(string $dbFile, string $table): bool
    {
        $pdo = new PDO('sqlite:' . $dbFile, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $statement = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='" . $table . "'"
        );

        return $statement !== false && $statement->fetchColumn() !== false;
    }

    public function testInitCreatesConfigFile(): void
    {
        $dir = Workspace::make();
        $output = new MemoryOutput();

        $this->dispatch(['init', '--dir=' . $dir], $output);

        $path = $dir . DIRECTORY_SEPARATOR . 'barbarian.json';
        $this->assertFileExists($path);
        $data = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('dsn', $data);
        $this->assertArrayHasKey('folder', $data);
        $this->assertStringContainsString('created', $output->plain());
    }

    public function testInitCreatesMigrationFolder(): void
    {
        $dir = Workspace::make();
        $output = new MemoryOutput();

        $this->dispatch(['init', '--dir=' . $dir], $output);

        $this->assertDirectoryExists($dir . DIRECTORY_SEPARATOR . 'migrations');
    }

    public function testInitWarnsWhenConfigExists(): void
    {
        $dir = Workspace::make();
        Workspace::writeFile($dir, 'barbarian.json', '{}');
        $output = new MemoryOutput();

        $this->dispatch(['init', '--dir=' . $dir], $output);

        $this->assertStringContainsString('already exists', $output->plain());
    }

    public function testCreateWritesMigrationWithoutConnectingToDatabase(): void
    {
        // Regression for create requiring a live database: an unreachable DSN
        // must not prevent a migration file from being scaffolded.
        [$migrationDir, $configPath] = $this->makeProject(null, 'mysql:host=203.0.113.10;dbname=nope');
        $output = new MemoryOutput();

        $this->dispatch(['create', '--config=' . $configPath], $output);

        $this->assertNotEmpty(glob($migrationDir . DIRECTORY_SEPARATOR . 'Migration_*.php'));
        $this->assertStringContainsString('Migration created', $output->plain());
    }

    public function testCreateErrorsWhenFolderMissing(): void
    {
        $dir = Workspace::make();
        $configPath = Workspace::writeFile($dir, 'barbarian.json', (string) json_encode([
            'dsn'      => 'sqlite::memory:',
            'username' => '',
            'password' => '',
            'folder'   => $dir . DIRECTORY_SEPARATOR . 'missing',
        ]));
        $output = new MemoryOutput();

        $this->dispatch(['create', '--config=' . $configPath], $output);

        $this->assertStringContainsString('does not exist', $output->plain());
    }

    public function testUpAppliesAllMigrations(): void
    {
        $namespace = Workspace::uniqueNamespace();
        [$migrationDir, $configPath, $dbFile] = $this->makeProject($namespace);
        Workspace::writeMigration($migrationDir, 'Migration_20240101000001', $namespace);
        Workspace::writeMigration($migrationDir, 'Migration_20240101000002', $namespace);
        $output = new MemoryOutput();

        $this->dispatch(['up', '--config=' . $configPath], $output);

        $plain = $output->plain();
        $this->assertStringContainsString('Migration_20240101000001::up()', $plain);
        $this->assertStringContainsString('Migration_20240101000002::up()', $plain);
        $this->assertTrue($this->tableExists($dbFile, 't_migration_20240101000001'));
        $this->assertTrue($this->tableExists($dbFile, 't_migration_20240101000002'));
    }

    public function testUpSingleMigrationByTimestamp(): void
    {
        // Regression for the CLI version lookup (was $migration[$version]).
        $namespace = Workspace::uniqueNamespace();
        [$migrationDir, $configPath, $dbFile] = $this->makeProject($namespace);
        Workspace::writeMigration($migrationDir, 'Migration_20240101000005', $namespace);
        $output = new MemoryOutput();

        $this->dispatch(['up', '--version=20240101000005', '--config=' . $configPath], $output);

        $this->assertStringContainsString('Migration_20240101000005::up()', $output->plain());
        $this->assertTrue($this->tableExists($dbFile, 't_migration_20240101000005'));
    }

    public function testUpSingleMigrationByFullName(): void
    {
        // The full-name branch is the one that crashed in the old CLI.
        $namespace = Workspace::uniqueNamespace();
        [$migrationDir, $configPath, $dbFile] = $this->makeProject($namespace);
        Workspace::writeMigration($migrationDir, 'Migration_20240101000007', $namespace);
        $output = new MemoryOutput();

        $this->dispatch(['up', '--version=Migration_20240101000007', '--config=' . $configPath], $output);

        $this->assertStringContainsString('Migration_20240101000007::up()', $output->plain());
        $this->assertTrue($this->tableExists($dbFile, 't_migration_20240101000007'));
    }

    public function testUpUnknownVersionReportsError(): void
    {
        [, $configPath] = $this->makeProject();
        $output = new MemoryOutput();

        $this->dispatch(['up', '--version=does_not_exist', '--config=' . $configPath], $output);

        $this->assertStringContainsString('could not be found', $output->plain());
    }

    public function testUpWithNoMigrations(): void
    {
        [, $configPath] = $this->makeProject();
        $output = new MemoryOutput();

        $this->dispatch(['up', '--config=' . $configPath], $output);

        $this->assertStringContainsString('No migrations found', $output->plain());
    }

    public function testDownRevertsMigrationsInReverseOrder(): void
    {
        // Regression for bulk down running in the wrong (ascending) order.
        $namespace = Workspace::uniqueNamespace();
        [$migrationDir, $configPath, $dbFile] = $this->makeProject($namespace);
        Workspace::writeMigration($migrationDir, 'Migration_20240101000001', $namespace);
        Workspace::writeMigration($migrationDir, 'Migration_20240101000002', $namespace);

        $this->dispatch(['up', '--config=' . $configPath], new MemoryOutput());

        $output = new MemoryOutput();
        $this->dispatch(['down', '--config=' . $configPath], $output);

        $plain = $output->plain();
        $first = strpos($plain, 'Migration_20240101000002::down()');
        $second = strpos($plain, 'Migration_20240101000001::down()');
        $this->assertNotFalse($first);
        $this->assertNotFalse($second);
        $this->assertLessThan($second, $first, 'Bulk down must run newest-first.');
        $this->assertFalse($this->tableExists($dbFile, 't_migration_20240101000001'));
        $this->assertFalse($this->tableExists($dbFile, 't_migration_20240101000002'));
    }

    public function testDownSingleMigration(): void
    {
        $namespace = Workspace::uniqueNamespace();
        [$migrationDir, $configPath, $dbFile] = $this->makeProject($namespace);
        Workspace::writeMigration($migrationDir, 'Migration_20240101000009', $namespace);

        $this->dispatch(['up', '--config=' . $configPath], new MemoryOutput());
        $output = new MemoryOutput();
        $this->dispatch(['down', '--version=20240101000009', '--config=' . $configPath], $output);

        $this->assertStringContainsString('Migration_20240101000009::down()', $output->plain());
        $this->assertFalse($this->tableExists($dbFile, 't_migration_20240101000009'));
    }

    public function testStatusShowsPendingThenUpThenDown(): void
    {
        $namespace = Workspace::uniqueNamespace();
        [$migrationDir, $configPath] = $this->makeProject($namespace);
        Workspace::writeMigration($migrationDir, 'Migration_20240101000011', $namespace);

        $pending = new MemoryOutput();
        $this->dispatch(['status', '--config=' . $configPath], $pending);
        $this->assertStringContainsString('[pending]', $pending->plain());
        $this->assertStringContainsString('Migration_20240101000011', $pending->plain());

        $this->dispatch(['up', '--config=' . $configPath], new MemoryOutput());
        $applied = new MemoryOutput();
        $this->dispatch(['status', '--config=' . $configPath], $applied);
        $this->assertStringContainsString('[up]', $applied->plain());

        $this->dispatch(['down', '--config=' . $configPath], new MemoryOutput());
        $reverted = new MemoryOutput();
        $this->dispatch(['status', '--config=' . $configPath], $reverted);
        $this->assertStringContainsString('[down]', $reverted->plain());
    }

    public function testStatusWithNoMigrations(): void
    {
        [, $configPath] = $this->makeProject();
        $output = new MemoryOutput();

        $this->dispatch(['status', '--config=' . $configPath], $output);

        $this->assertStringContainsString('No migrations found', $output->plain());
    }

    public function testMissingConfigReportsError(): void
    {
        $output = new MemoryOutput();

        $this->dispatch(['up', '--config=/no/such/barbarian.json'], $output);

        $this->assertStringContainsString('Configuration file not found', $output->plain());
    }
}

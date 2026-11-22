<?php

declare(strict_types=1);

namespace Test\InitPHP\Barbarian;

use InitPHP\Barbarian\Migrations;
use InitPHP\Barbarian\MigrationStatus;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use Test\InitPHP\Barbarian\Fixtures\AlwaysFalseMigration;
use Test\InitPHP\Barbarian\Fixtures\CreateThingMigration;
use Test\InitPHP\Barbarian\Fixtures\SpyMigration;
use Test\InitPHP\Barbarian\Support\Workspace;

final class MigrationsTest extends TestCase
{
    private function pdo(): PDO
    {
        return new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    private function tableExists(PDO $pdo, string $table): bool
    {
        $statement = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='" . $table . "'",
        );

        return $statement !== false && $statement->fetchColumn() !== false;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function versionRow(PDO $pdo, string $versionName, string $table = 'migrations_versions'): ?array
    {
        $statement = $pdo->prepare('SELECT * FROM ' . $table . ' WHERE version_name = :v');
        $statement->execute([':v' => $versionName]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function testMissingFolderThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Migrations($this->pdo(), '/this/path/does/not/exist');
    }

    public function testInvalidTableNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Migrations($this->pdo(), Workspace::make(), ['migrationTable' => 'bad-name!']);
    }

    public function testCreatesVersionTableOnConstruction(): void
    {
        $pdo = $this->pdo();
        new Migrations($pdo, Workspace::make(), ['migrationTable' => 'custom_versions']);

        $this->assertTrue($this->tableExists($pdo, 'custom_versions'));
    }

    public function testDiscoversGlobalNamespaceMigration(): void
    {
        $dir = Workspace::make();
        $name = 'Migration_' . bin2hex(random_bytes(5));
        Workspace::writeMigration($dir, $name);

        $migrations = (new Migrations($this->pdo(), $dir))->getMigrations();

        $this->assertArrayHasKey($name, $migrations);
        $this->assertSame($name, $migrations[$name]);
    }

    public function testDiscoversNamespacedMigration(): void
    {
        // Regression for the missing setNamespace() bug: namespaced migrations
        // must be discoverable when a namespace is configured.
        $dir = Workspace::make();
        $namespace = Workspace::uniqueNamespace();
        $name = 'Migration_' . bin2hex(random_bytes(5));
        Workspace::writeMigration($dir, $name, $namespace);

        $manager = new Migrations($this->pdo(), $dir, ['namespace' => $namespace]);

        $this->assertSame([], $manager->getErrors());
        $this->assertArrayHasKey($name, $manager->getMigrations());
        $this->assertSame($namespace . '\\' . $name, $manager->getMigrations()[$name]);
        $this->assertSame($namespace, $manager->getNamespace());
    }

    public function testCollectsErrorForNonMigrationClass(): void
    {
        $dir = Workspace::make();
        $name = 'Migration_' . bin2hex(random_bytes(5));
        Workspace::writeFile(
            $dir,
            $name . '.php',
            "<?php\nclass {$name} {}\n",
        );

        $manager = new Migrations($this->pdo(), $dir);

        $this->assertArrayNotHasKey($name, $manager->getMigrations());
        $this->assertNotEmpty($manager->getErrors());
    }

    public function testUpMigrationAppliesAndRecords(): void
    {
        $pdo = $this->pdo();
        $manager = new Migrations($pdo, Workspace::make());

        $this->assertTrue($manager->upMigration(new CreateThingMigration()));
        $this->assertTrue($this->tableExists($pdo, 'things'));

        $row = $this->versionRow($pdo, 'CreateThingMigration');
        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row['status']);
    }

    public function testUpMigrationSkippedWhenAlreadyUp(): void
    {
        $manager = new Migrations($this->pdo(), Workspace::make());
        $spy = new SpyMigration();

        $this->assertTrue($manager->upMigration($spy));
        $this->assertFalse($manager->upMigration($spy));
        $this->assertSame(1, $spy->upCalls, 'up() must not run again when already up.');
    }

    public function testForceReappliesUpMigration(): void
    {
        $manager = new Migrations($this->pdo(), Workspace::make());
        $spy = new SpyMigration();

        $manager->upMigration($spy);
        $this->assertTrue($manager->upMigration($spy, true));
        $this->assertSame(2, $spy->upCalls);
    }

    public function testDownMigrationRevertsAfterUp(): void
    {
        // Regression for the strict string/int status comparison bug: down()
        // used to never run because "1" === 1 is false under most drivers.
        $pdo = $this->pdo();
        $manager = new Migrations($pdo, Workspace::make());
        $migration = new CreateThingMigration();

        $manager->upMigration($migration);
        $this->assertTrue($this->tableExists($pdo, 'things'));

        $this->assertTrue($manager->downMigration($migration));
        $this->assertFalse($this->tableExists($pdo, 'things'));

        $row = $this->versionRow($pdo, 'CreateThingMigration');
        $this->assertNotNull($row);
        $this->assertSame(0, (int) $row['status']);
    }

    public function testDownMigrationSkippedWhenAlreadyDown(): void
    {
        $manager = new Migrations($this->pdo(), Workspace::make());
        $spy = new SpyMigration();

        $manager->upMigration($spy);
        $this->assertTrue($manager->downMigration($spy));
        $this->assertFalse($manager->downMigration($spy));
        $this->assertSame(1, $spy->downCalls);
    }

    public function testDownMigrationSkippedWhenNeverApplied(): void
    {
        $manager = new Migrations($this->pdo(), Workspace::make());
        $spy = new SpyMigration();

        $this->assertFalse($manager->downMigration($spy));
        $this->assertSame(0, $spy->downCalls);
    }

    public function testReUpAfterDown(): void
    {
        $manager = new Migrations($this->pdo(), Workspace::make());
        $spy = new SpyMigration();

        $manager->upMigration($spy);
        $manager->downMigration($spy);

        $this->assertTrue($manager->upMigration($spy));
        $this->assertSame(2, $spy->upCalls);
    }

    public function testUpMigrationDoesNotRecordWhenMigrationReturnsFalse(): void
    {
        // Regression: up()/down() return values must be honoured.
        $pdo = $this->pdo();
        $manager = new Migrations($pdo, Workspace::make());

        $this->assertFalse($manager->upMigration(new AlwaysFalseMigration()));
        $this->assertNull($this->versionRow($pdo, 'AlwaysFalseMigration'));
    }

    public function testStatusReflectsLifecycle(): void
    {
        $manager = new Migrations($this->pdo(), Workspace::make());
        $spy = new SpyMigration();

        $this->assertNull($manager->status($spy));

        $manager->upMigration($spy);
        $this->assertSame(MigrationStatus::Up, $manager->status($spy));

        $manager->downMigration($spy);
        $this->assertSame(MigrationStatus::Down, $manager->status($spy));
    }

    public function testGetStatusesReportsDiscoveredMigrationsAsPending(): void
    {
        $dir = Workspace::make();
        $namespace = Workspace::uniqueNamespace();
        $name = 'Migration_' . bin2hex(random_bytes(5));
        Workspace::writeMigration($dir, $name, $namespace);

        $statuses = (new Migrations($this->pdo(), $dir, ['namespace' => $namespace]))->getStatuses();

        $this->assertArrayHasKey($name, $statuses);
        $this->assertNull($statuses[$name]);
    }
}

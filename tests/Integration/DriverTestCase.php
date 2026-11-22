<?php

declare(strict_types=1);

namespace Test\InitPHP\Barbarian\Integration;

use InitPHP\Barbarian\Migrations;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Test\InitPHP\Barbarian\Fixtures\AlwaysFalseMigration;
use Test\InitPHP\Barbarian\Fixtures\CreateThingMigration;
use Test\InitPHP\Barbarian\Fixtures\SpyMigration;
use Test\InitPHP\Barbarian\Support\Workspace;

use function getenv;
use function is_string;

/**
 * The migration lifecycle exercised against a real database server.
 *
 * Concrete subclasses supply the connection; when none is configured (or the
 * server is unreachable) every test is skipped, so the suite stays green on
 * machines without that database while still running in CI.
 */
abstract class DriverTestCase extends TestCase
{
    private PDO $pdo;

    private string $folder;

    /**
     * The connection for this driver, or null to skip the suite.
     */
    abstract protected function makePdo(): ?PDO;

    /**
     * Opens a connection from `<PREFIX>_DSN`, `<PREFIX>_USER` and
     * `<PREFIX>_PASSWORD` environment variables, returning null when the DSN is
     * absent or the server cannot be reached.
     */
    protected static function pdoFromEnv(string $prefix): ?PDO
    {
        $dsn = getenv($prefix . '_DSN');
        if (!is_string($dsn) || $dsn === '') {
            return null;
        }

        $user = getenv($prefix . '_USER');
        $password = getenv($prefix . '_PASSWORD');

        try {
            return new PDO(
                $dsn,
                is_string($user) ? $user : null,
                is_string($password) ? $password : null,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
        } catch (PDOException) {
            return null;
        }
    }

    protected function setUp(): void
    {
        $pdo = $this->makePdo();
        if ($pdo === null) {
            self::markTestSkipped('No reachable database configured for ' . static::class . '.');
        }

        $this->pdo = $pdo;
        $this->folder = Workspace::make();
        $this->dropTables();
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo)) {
            $this->dropTables();
        }
    }

    private function dropTables(): void
    {
        foreach (['things', 'migrations_versions'] as $table) {
            $this->pdo->exec('DROP TABLE IF EXISTS ' . $table);
        }
    }

    private function manager(): Migrations
    {
        return new Migrations($this->pdo, $this->folder);
    }

    private function tableExists(string $table): bool
    {
        try {
            $this->pdo->query('SELECT 1 FROM ' . $table . ' WHERE 1 = 0');

            return true;
        } catch (PDOException) {
            return false;
        }
    }

    private function versionStatus(string $versionName): ?int
    {
        $statement = $this->pdo->prepare(
            'SELECT status FROM migrations_versions WHERE version_name = :version_name',
        );
        $statement->execute([':version_name' => $versionName]);
        $value = $statement->fetchColumn();

        return $value === false ? null : (int) $value;
    }

    public function testVersionTableIsCreated(): void
    {
        // Constructing the manager runs the driver-specific CREATE TABLE.
        // A broken grammar (e.g. the old PostgreSQL DDL) would throw here.
        $this->manager();

        $this->assertTrue($this->tableExists('migrations_versions'));
    }

    public function testUpThenDownLifecycle(): void
    {
        // The headline cross-driver regression: under MySQL/PostgreSQL the
        // status column reads back as a string, which used to break down().
        $manager = $this->manager();
        $migration = new CreateThingMigration();

        $this->assertTrue($manager->upMigration($migration));
        $this->assertTrue($this->tableExists('things'));
        $this->assertSame(1, $this->versionStatus('CreateThingMigration'));

        $this->assertTrue($manager->downMigration($migration));
        $this->assertFalse($this->tableExists('things'));
        $this->assertSame(0, $this->versionStatus('CreateThingMigration'));
    }

    public function testUpIsIdempotentAndForceReruns(): void
    {
        $manager = $this->manager();
        $spy = new SpyMigration();

        $this->assertTrue($manager->upMigration($spy));
        $this->assertFalse($manager->upMigration($spy));
        $this->assertSame(1, $spy->upCalls);

        $this->assertTrue($manager->upMigration($spy, true));
        $this->assertSame(2, $spy->upCalls);
    }

    public function testReUpAfterDown(): void
    {
        $manager = $this->manager();
        $spy = new SpyMigration();

        $this->assertTrue($manager->upMigration($spy));
        $this->assertTrue($manager->downMigration($spy));
        $this->assertTrue($manager->upMigration($spy));
        $this->assertSame(2, $spy->upCalls);
        $this->assertSame(1, $spy->downCalls);
    }

    public function testFalseReturnIsNotRecorded(): void
    {
        $manager = $this->manager();

        $this->assertFalse($manager->upMigration(new AlwaysFalseMigration()));
        $this->assertNull($this->versionStatus('AlwaysFalseMigration'));
    }
}

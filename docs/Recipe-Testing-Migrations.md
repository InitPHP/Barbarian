# Recipe: Testing Migrations

The cleanest way to test migrations is against an **in-memory SQLite** database:
it needs no server, starts empty for every test, and disappears afterwards.

## A minimal PHPUnit test

```php
<?php

declare(strict_types=1);

namespace Tests;

use InitPHP\Barbarian\Migrations;
use PDO;
use PHPUnit\Framework\TestCase;

final class MigrationsTest extends TestCase
{
    private function pdo(): PDO
    {
        return new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    public function testUpThenDown(): void
    {
        $pdo = $this->pdo();
        $migrations = new Migrations($pdo, __DIR__ . '/../migrations');

        foreach ($migrations->getMigrations() as $class) {
            self::assertTrue($migrations->upMigration(new $class()));
        }

        // assert the schema your migrations create exists...
        $tables = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type='table'"
        )->fetchAll(PDO::FETCH_COLUMN);

        self::assertContains('users', $tables);

        foreach (array_reverse($migrations->getMigrations()) as $class) {
            $migrations->downMigration(new $class());
        }
    }
}
```

## Tips

- **Keep one `PDO` per test.** An in-memory SQLite database only lives as long as
  its connection. Reuse the same `$pdo` for the manager and your assertions.
- **Make migration SQL portable enough for SQLite,** or test driver-specific
  migrations against the real engine — see [Database Drivers](Database-Drivers.md).
- **Assert status, not just side effects:**

  ```php
  use InitPHP\Barbarian\MigrationStatus;

  $migration = new \App\Migrations\Migration_20240101000000();
  $migrations->upMigration($migration);
  self::assertSame(MigrationStatus::Up, $migrations->status($migration));
  ```

- **Test the "not recorded on failure" contract** by having a migration's `up()`
  return `false` and asserting `status()` stays `null`.

## Testing against MySQL / PostgreSQL in CI

When you need to test driver-specific SQL, point the manager at a real server
via environment variables and skip the test when the server is absent:

```php
protected function pdo(): ?PDO
{
    $dsn = getenv('TEST_DSN');
    if (!is_string($dsn) || $dsn === '') {
        return null; // markTestSkipped() in setUp()
    }

    return new PDO($dsn, getenv('TEST_USER') ?: null, getenv('TEST_PASS') ?: null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}
```

In GitHub Actions, provide the databases as `services:` (MySQL and PostgreSQL
containers) and set those environment variables for the test step.

## See also

- [Recipe: Deploying Migrations](Recipe-Deploying-Migrations.md)
- [Database Drivers](Database-Drivers.md)

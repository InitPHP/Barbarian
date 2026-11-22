# Writing Migrations

## The contract

Every migration implements [`MigrationInterface`](API-Reference.md#migrationinterface):

```php
interface MigrationInterface
{
    public function up(QueryInterface $query): bool;
    public function down(QueryInterface $query): bool;
    public function getName(): string;
}
```

In practice you extend [`MigrationAbstract`](API-Reference.md#migrationabstract),
which implements `getName()` for you (it returns the unqualified class name),
leaving you to write only `up()` and `down()`.

## Anatomy of a migration

```php
<?php

declare(strict_types=1);

namespace App\Migrations;

use InitPHP\Barbarian\MigrationAbstract;
use InitPHP\Barbarian\QueryInterface;

final class Migration_20240101000000 extends MigrationAbstract
{
    public function up(QueryInterface $query): bool
    {
        $query->query('CREATE TABLE IF NOT EXISTS `users` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

        return true;
    }

    public function down(QueryInterface $query): bool
    {
        $query->query('DROP TABLE IF EXISTS `users`');

        return true;
    }
}
```

## Naming conventions

| Aspect | Rule |
| --- | --- |
| **File name** | `Migration_<timestamp>.php`, e.g. `Migration_20240101000000.php`. Only files matching `Migration_*.php` are discovered. |
| **Class name** | Must equal the file name, so the file can be `require`d and the class resolved. |
| **Version name** | The unqualified class name. It is the key in the version table, so it must be unique across your migration set. |

The [`create`](CLI-Commands.md#create--scaffold-a-migration) command follows these
conventions automatically and produces `Migration_<YmdHis>` files.

> Because the version name is the **unqualified** class name, two migrations in
> different namespaces but with the same short name would collide. Keep your
> timestamps unique.

## The query gateway

Both `up()` and `down()` receive a [`QueryInterface`](API-Reference.md#queryinterface):

```php
public function query(string $sql, ?array $arguments = null): \PDOStatement;
```

**Bound parameters** — pass them as the second argument:

```php
$query->query('INSERT INTO settings (k, v) VALUES (:k, :v)', [
    ':k' => 'installed_at',
    ':v' => date('c'),
]);
```

**Reading results** — it returns the executed `PDOStatement`:

```php
$statement = $query->query('SELECT COUNT(*) FROM users');
$count = (int) $statement->fetchColumn();
```

**Errors** — a failed statement throws a `\PDOException` rather than returning a
falsy value. The connection is always in `PDO::ERRMODE_EXCEPTION` mode (the
[`QueryRunner`](API-Reference.md#queryrunner) forces it), so you never have to
check return values for `false`.

## Return values matter

`up()` and `down()` must return `bool`:

| Return | Effect |
| --- | --- |
| `true` | The change succeeded; Barbarian records the new [status](API-Reference.md#migrationstatus). |
| `false` | The change did **not** happen; Barbarian leaves the recorded status untouched, so the migration can be retried. |

```php
public function up(QueryInterface $query): bool
{
    if (!$this->preconditionMet($query)) {
        return false; // nothing recorded; safe to run again later
    }

    $query->query('...');

    return true;
}
```

Throwing an exception from `up()`/`down()` aborts the run entirely and, under
the CLI, is reported as an error (see [Exceptions](Exceptions.md)).

## How migrations are applied

| Situation | `upMigration()` | `downMigration()` |
| --- | --- | --- |
| Never recorded | Runs `up()`, records **Up** → `true` | Nothing to revert → `false` |
| Currently **Down** | Runs `up()`, records **Up** → `true` | Already down → `false` (unless `force`) |
| Currently **Up** | Already up → `false` (unless `force`) | Runs `down()`, records **Down** → `true` |
| `up()`/`down()` returns `false` | No state change → `false` | No state change → `false` |

The optional `force` argument re-runs a migration even when it is already in the
target state — see [`upMigration()`](API-Reference.md#upmigration) and
[`downMigration()`](API-Reference.md#downmigration).

## Portability

The SQL you write is yours to keep portable across engines. If you need to
branch on the driver, the object passed to your migration is a
[`QueryRunner`](API-Reference.md#queryrunner), which exposes `driver()`:

```php
public function up(QueryInterface $query): bool
{
    $driver = $query instanceof \InitPHP\Barbarian\QueryRunner ? $query->driver() : null;

    // ... emit driver-appropriate SQL ...

    return true;
}
```

See [Database Drivers](Database-Drivers.md) for engine-specific notes.

## Next steps

- [CLI Commands](CLI-Commands.md) — running migrations from the terminal.
- [Programmatic Usage](Programmatic-Usage.md) — running them from code.
- [API Reference](API-Reference.md) — every method, typed.

# Writing migrations

## The contract

Every migration implements `InitPHP\Barbarian\MigrationInterface`:

```php
interface MigrationInterface
{
    public function up(QueryInterface $query): bool;
    public function down(QueryInterface $query): bool;
    public function getName(): string;
}
```

In practice you extend `InitPHP\Barbarian\MigrationAbstract`, which implements
`getName()` for you (it returns the unqualified class name), leaving you to write
only `up()` and `down()`.

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

### Naming conventions

- **File name:** `Migration_<timestamp>.php`, e.g. `Migration_20240101000000.php`.
  Only files matching `Migration_*.php` are discovered.
- **Class name:** must equal the file name (so the file can be `require`d and the
  class resolved).
- **Version name:** the unqualified class name. This is the key used in the
  version table, so it must be unique across your migration set.

The `create` command follows these conventions automatically:

```bash
vendor/bin/barbarian create
# -> Migration created: Migration_20240101000000
```

## The query gateway

Both `up()` and `down()` receive a `QueryInterface`:

```php
public function query(string $sql, ?array $arguments = null): \PDOStatement;
```

- Pass bound parameters as the second argument:

  ```php
  $query->query('INSERT INTO settings (k, v) VALUES (:k, :v)', [
      ':k' => 'installed_at',
      ':v' => date('c'),
  ]);
  ```

- It returns the executed `PDOStatement`, so you can read results:

  ```php
  $statement = $query->query('SELECT COUNT(*) FROM users');
  $count = (int) $statement->fetchColumn();
  ```

- Failures throw a `\PDOException` rather than returning a falsy value.

## Return values matter

`up()` and `down()` must return `bool`:

- `true` — the change succeeded; Barbarian records the new state.
- `false` — the change did **not** happen; Barbarian leaves the recorded state
  untouched so the migration can be retried.

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

Throwing an exception from `up()`/`down()` aborts the run entirely and, under the
CLI, is reported as an error.

## How migrations are applied

- An unapplied migration is applied and recorded with status **Up**.
- A migration already **Up** is skipped (unless forced).
- Reverting runs `down()` and records status **Down**.
- A migration that was never applied cannot be reverted.

See [Programmatic usage](./programmatic-usage.md) for the exact method semantics
and the `force` flag.

## Next steps

- [Command-line usage](./cli-usage.md)
- [Database drivers](./drivers.md)

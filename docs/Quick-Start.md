# Quick Start

A five-minute tour. Every snippet runs as-is. We will use the CLI; the same
thing is possible [from code](Programmatic-Usage.md).

## 1. Generate a configuration file

```bash
vendor/bin/barbarian init
```

This writes a `barbarian.json` template into the current directory **and**
creates the `migrations/` folder it points at:

```json
{
    "dsn": "mysql:host=localhost;dbname=test;charset=utf8mb4",
    "username": "root",
    "password": "",
    "folder": "/path/to/migrations/",
    "table_name": "migrations_versions",
    "namespace": null
}
```

Edit it to match your database. See [Configuration](Configuration.md) for every key.

## 2. Create a migration

```bash
vendor/bin/barbarian create
# -> Migration created: Migration_20240101000000
```

`create` never connects to the database — it only needs the configuration to
know *where* and under which *namespace* to write the file. The generated class
is empty:

```php
<?php

declare(strict_types=1);

use InitPHP\Barbarian\MigrationAbstract;
use InitPHP\Barbarian\QueryInterface;

class Migration_20240101000000 extends MigrationAbstract
{
    public function up(QueryInterface $query): bool
    {
        return true;
    }

    public function down(QueryInterface $query): bool
    {
        return true;
    }
}
```

## 3. Fill in `up()` and `down()`

```php
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
```

Returning `true` tells Barbarian to record the change. Returning `false` (or
throwing) leaves the recorded state untouched — see [Writing Migrations](Writing-Migrations.md#return-values-matter).

## 4. Check the status

```bash
vendor/bin/barbarian status
```

```text
  [pending]   Migration_20240101000000
```

`pending` means the migration has been discovered but never applied.

## 5. Apply and revert

```bash
vendor/bin/barbarian up      # runs up() for every pending/down migration
# -> Migration_20240101000000::up() has been executed.

vendor/bin/barbarian status
# -> [up]        Migration_20240101000000

vendor/bin/barbarian down    # runs down() in reverse order
# -> Migration_20240101000000::down() has been executed.

vendor/bin/barbarian status
# -> [down]      Migration_20240101000000
```

You can also target a single migration by timestamp or full class name:

```bash
vendor/bin/barbarian up --version=20240101000000
vendor/bin/barbarian down --version=Migration_20240101000000
```

## 6. The same, from code

```php
require 'vendor/autoload.php';

use InitPHP\Barbarian\Migrations;

$pdo = new PDO('mysql:host=localhost;dbname=test;charset=utf8mb4', 'root', '');

$migrations = new Migrations($pdo, __DIR__ . '/migrations', [
    'namespace'      => null,
    'migrationTable' => 'migrations_versions',
]);

foreach ($migrations->getMigrations() as $class) {
    $migrations->upMigration(new $class());
}
```

## Where to go next

- [Writing Migrations](Writing-Migrations.md) — the contract in detail.
- [CLI Commands](CLI-Commands.md) — every command and flag.
- [Programmatic Usage](Programmatic-Usage.md) — driving the manager directly.
- [Database Drivers](Database-Drivers.md) — MySQL, SQLite, PostgreSQL.

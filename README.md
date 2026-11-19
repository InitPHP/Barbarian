# Barbarian

A small, dependency-light database migration library for PHP. It discovers your
migration classes on disk, keeps a version table in sync, and runs the
`up()`/`down()` side of each migration either programmatically or through a
simple command-line interface.

[![Designed & Maintained with Tan](https://muhammetsafak.com/badges/designed-maintained-with-tan.svg)](https://muhammetsafak.com/tan/)
[![CI](https://github.com/InitPHP/Barbarian/actions/workflows/ci.yml/badge.svg)](https://github.com/InitPHP/Barbarian/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/initphp/barbarian/v)](https://packagist.org/packages/initphp/barbarian)
[![License](https://poser.pugx.org/initphp/barbarian/license)](https://packagist.org/packages/initphp/barbarian)

## Features

- Plain PDO under the hood — works with **MySQL/MariaDB**, **SQLite** and **PostgreSQL**.
- A tracked version table so each migration is applied (or reverted) exactly once.
- Idempotent `up`/`down` with an optional `force` to re-run.
- A small CLI (`init`, `create`, `status`, `up`, `down`) built on [InitPHP Console](https://github.com/InitPHP/Console).
- Fully typed, PSR-12 compliant, analysed with PHPStan and covered by tests.

## Requirements

- PHP 8.1 or higher
- `ext-pdo` and the PDO driver for your database (`pdo_mysql`, `pdo_sqlite` or `pdo_pgsql`)
- `ext-json`
- [initphp/console](https://github.com/InitPHP/Console) `^2.1` (installed automatically)

## Installation

```bash
composer require initphp/barbarian
```

## Writing a migration

A migration extends `InitPHP\Barbarian\MigrationAbstract` and implements `up()`
and `down()`. Each method receives a `QueryInterface` to run statements with and
returns a `bool`: returning `false` (or throwing) tells the runner **not** to
record the state change, so the migration can be retried.

By convention, files are named `Migration_<timestamp>.php` and the class name
matches the file name.

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
            `email` VARCHAR(255) NOT NULL,
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

## Programmatic usage

```php
require 'vendor/autoload.php';

use InitPHP\Barbarian\Migrations;

$pdo = new PDO('mysql:host=localhost;dbname=test;charset=utf8mb4', 'root', '');

$migrations = new Migrations($pdo, __DIR__ . '/migrations', [
    'migrationTable' => 'migrations_versions',
    'namespace'      => 'App\\Migrations', // use null for the global namespace
]);

// Apply (up) a single migration:
$migrations->upMigration(new \App\Migrations\Migration_20240101000000());

// Revert (down) a single migration:
$migrations->downMigration(new \App\Migrations\Migration_20240101000000());

// Or iterate over every discovered migration:
foreach ($migrations->getMigrations() as $version => $class) {
    $migrations->upMigration(new $class());
}
```

`upMigration()` and `downMigration()` accept a second `bool $force` argument to
re-run a migration that has already been applied/reverted. Problems found while
scanning the folder are available via `getErrors()`.

## Command-line usage

First create a `barbarian.json` configuration file in your project root:

```bash
vendor/bin/barbarian init
```

This writes a template you then edit:

```json
{
    "dsn": "mysql:host=localhost;dbname=test;charset=utf8mb4",
    "username": "root",
    "password": "",
    "folder": "/path/to/migrations/",
    "table_name": "migrations_versions",
    "namespace": "App\\Migrations"
}
```

| Key          | Required | Description                                                            |
|--------------|----------|------------------------------------------------------------------------|
| `dsn`        | yes      | PDO connection string.                                                  |
| `username`   | yes      | Database user.                                                          |
| `password`   | yes      | Database password (may be empty).                                       |
| `folder`     | yes      | Directory containing the `Migration_*.php` files.                       |
| `table_name` | no       | Version table name (default `migrations_versions`).                     |
| `namespace`  | no       | Namespace the migration classes live under, or `null` if global.        |

Then:

```bash
# Create a new, empty migration in the configured folder (no database needed):
vendor/bin/barbarian create

# Show the status (up / down / pending) of every migration:
vendor/bin/barbarian status

# Apply every migration (ascending order):
vendor/bin/barbarian up

# Apply a single migration (timestamp or full class name):
vendor/bin/barbarian up --version=20240101000000
vendor/bin/barbarian up --version=Migration_20240101000000

# Revert every migration (reverse order):
vendor/bin/barbarian down

# Revert a single migration:
vendor/bin/barbarian down --version=20240101000000
```

Every command accepts `--config=/path/to/barbarian.json` to point at a specific
configuration file (it defaults to `./barbarian.json`).

## Supported drivers

The version table DDL is generated per driver (`mysql`, `sqlite`, `pgsql`); any
other driver falls back to the MySQL grammar. The migration statements you write
in `up()`/`down()` are, of course, your own responsibility to keep portable.

## Documentation

In-depth guides with examples live in the [`docs/`](./docs) directory:

- [Installation](./docs/installation.md)
- [Configuration](./docs/configuration.md)
- [Writing migrations](./docs/writing-migrations.md)
- [Command-line usage](./docs/cli-usage.md)
- [Programmatic usage](./docs/programmatic-usage.md)
- [Database drivers](./docs/drivers.md)
- [Extending Barbarian](./docs/extending.md)

## Development

```bash
composer install
composer test     # run the PHPUnit suite
composer cs       # check coding standards (PSR-12)
composer stan     # run static analysis (PHPStan)
composer qa       # all of the above
```

## Credits

- [Muhammet ŞAFAK](https://muhammetsafak.com) <<info@muhammetsafak.com>>

## License

Released under the [MIT License](./LICENSE).

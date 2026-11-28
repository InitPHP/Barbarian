# InitPHP Barbarian — Wiki

Welcome to the official documentation for **`initphp/barbarian`** — a small,
dependency-light database migration library for PHP. It discovers your
migration classes on disk, keeps a version table in sync, and runs the
`up()`/`down()` side of each migration either from your own code or through a
simple command-line interface.

```bash
composer require initphp/barbarian
```

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

```bash
vendor/bin/barbarian status   # up / down / pending for every migration
vendor/bin/barbarian up       # apply all migrations
vendor/bin/barbarian down     # revert all migrations (reverse order)
```

## The building blocks

| Type | Purpose |
| --- | --- |
| [`Migrations`](API-Reference.md#migrations) | Discovers migrations, keeps the version table in sync, runs up()/down(). |
| [`MigrationInterface`](API-Reference.md#migrationinterface) | The contract every migration fulfils. |
| [`MigrationAbstract`](API-Reference.md#migrationabstract) | Base class implementing `getName()` for you. |
| [`QueryInterface`](API-Reference.md#queryinterface) | The query gateway handed to each migration. |
| [`QueryRunner`](API-Reference.md#queryrunner) | Default `QueryInterface`: a thin PDO wrapper. |
| [`MigrationStatus`](API-Reference.md#migrationstatus) | Enum: `Down = 0`, `Up = 1`. |
| [`Schema\Grammar`](API-Reference.md#grammar) | Driver-specific version table DDL and identifier quoting. |
| [`MigrationException`](API-Reference.md#migrationexception) | Raised for failures in Barbarian itself. |
| [`Console\*`](API-Reference.md#console-layer) | Configuration loading, the manager factory and the CLI commands. |

## Start here

- **New to the package?** Read [Installation](Installation.md), then [Quick Start](Quick-Start.md).
- **Writing migrations?** See [Writing Migrations](Writing-Migrations.md).
- **Configuring it?** See [Configuration](Configuration.md) for both the `Migrations` options and `barbarian.json`.
- **Using the CLI?** See [CLI Commands](CLI-Commands.md).
- **Driving it from code?** See [Programmatic Usage](Programmatic-Usage.md).
- **Targeting a specific database?** See [Database Drivers](Database-Drivers.md).
- **Looking for a specific method?** The full [API Reference](API-Reference.md) lists every class member.
- **Coming from 1.x?** Read [Upgrade from 1.x](Upgrade-from-1.x.md).

## Core concepts

| Term | Meaning |
| --- | --- |
| **Migration** | A class implementing [`MigrationInterface`](API-Reference.md#migrationinterface) (usually via [`MigrationAbstract`](API-Reference.md#migrationabstract)) with `up()` and `down()`. |
| **Version name** | The unqualified class name (e.g. `Migration_20240101000000`); the unique key in the version table. |
| **Version table** | A table (default `migrations_versions`) recording each migration's [status](API-Reference.md#migrationstatus) and last change time. |
| **Manager** | [`Migrations`](API-Reference.md#migrations) — discovers migrations, ensures the version table and runs migrations. |
| **Query gateway** | [`QueryInterface`](API-Reference.md#queryinterface) (implemented by [`QueryRunner`](API-Reference.md#queryrunner)) — the object passed to every `up()`/`down()`. |

## Package metadata

- **License**: MIT
- **Minimum PHP**: 8.2 (CI matrix runs 8.2 – 8.6)
- **Runtime dependencies**: [`initphp/console`](https://packagist.org/packages/initphp/console) `^3.0`
- **Required extensions**: `ext-pdo`, `ext-json` (plus the PDO driver for your database)
- **Supported databases**: MySQL/MariaDB, SQLite, PostgreSQL
- **Packagist**: [`initphp/barbarian`](https://packagist.org/packages/initphp/barbarian)
- **Source**: [github.com/InitPHP/Barbarian](https://github.com/InitPHP/Barbarian)
- **Issues**: [github.com/InitPHP/Barbarian/issues](https://github.com/InitPHP/Barbarian/issues)
- **Discussions**: [github.com/orgs/InitPHP/discussions](https://github.com/orgs/InitPHP/discussions)
- **Security**: [`SECURITY.md`](https://github.com/InitPHP/.github/blob/main/SECURITY.md)

If something in this wiki is unclear, ambiguous, or wrong, please
[open an issue](https://github.com/InitPHP/Barbarian/issues/new) —
documentation fixes are reviewed eagerly.

# Installation

## Requirements

| Requirement | Version / Notes |
| --- | --- |
| PHP | 8.2 or higher |
| `ext-pdo` | Required |
| PDO driver | `pdo_mysql`, `pdo_sqlite` or `pdo_pgsql` for your database |
| `ext-json` | Required (used to read `barbarian.json`) |
| [`initphp/console`](https://packagist.org/packages/initphp/console) | `^2.1` (installed automatically) |

## Install with Composer

```bash
composer require initphp/barbarian
```

This installs the library and exposes the CLI entry point at
`vendor/bin/barbarian`.

## Verify the installation

```bash
vendor/bin/barbarian help
```

You should see the command listing:

```text
init     : Generates a barbarian.json configuration file.
create   : Creates a new migration class.
status   : Shows the status of each migration.
up       : Runs the up() method of the migrations.
down     : Runs the down() method of the migrations.
```

## Autoloading and migrations

The library itself is autoloaded through Composer's PSR-4 mapping
(`InitPHP\Barbarian\` → `src/`).

Your **migration files**, however, are discovered by globbing
`Migration_*.php` inside the configured folder and are `require`d directly —
they do **not** need to be registered with Composer's autoloader. If your
migration classes declare a namespace, tell Barbarian about it via the
[`namespace` option](Configuration.md#the-migrations-constructor).

## Next steps

- [Quick Start](Quick-Start.md) — a five-minute tour.
- [Writing Migrations](Writing-Migrations.md) — the migration contract.
- [Configuration](Configuration.md) — options and `barbarian.json`.

# Barbarian Documentation

Barbarian is a small database migration library for PHP. These guides cover
everything from installation to extending the internals.

## Table of contents

1. [Installation](./installation.md) — requirements and Composer setup.
2. [Configuration](./configuration.md) — the `barbarian.json` file and the `Migrations` options.
3. [Writing migrations](./writing-migrations.md) — the migration contract and conventions.
4. [Command-line usage](./cli-usage.md) — the `init`, `create`, `up` and `down` commands.
5. [Programmatic usage](./programmatic-usage.md) — driving migrations from your own code.
6. [Database drivers](./drivers.md) — MySQL/MariaDB, SQLite and PostgreSQL specifics.
7. [Extending Barbarian](./extending.md) — custom query runners, factories and the version grammar.

## Core concepts

- **Migration** — a class implementing `InitPHP\Barbarian\MigrationInterface`
  (usually by extending `MigrationAbstract`) with an `up()` and a `down()` method.
- **Version name** — the unqualified class name (e.g. `Migration_20240101000000`),
  used as the unique key in the version table.
- **Version table** — a table (default `migrations_versions`) that records, for
  each migration, its current [status](./programmatic-usage.md#status) and the
  time it last changed.
- **Manager** — `InitPHP\Barbarian\Migrations`, which discovers migrations,
  ensures the version table exists and runs migrations on demand.
- **Query gateway** — `InitPHP\Barbarian\QueryInterface` (implemented by
  `QueryRunner`), the object handed to every `up()`/`down()` call.

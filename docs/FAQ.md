# FAQ

## Which databases are supported?

MySQL/MariaDB, SQLite and PostgreSQL have first-class support for the version
table. Any other PDO driver falls back to the MySQL grammar. The statements you
write inside migrations can target whatever your driver supports. See
[Database Drivers](Database-Drivers.md).

## Do my migration classes need to be in Composer's autoloader?

No. Migration files are discovered by globbing `Migration_*.php` in the
configured folder and are `require`d directly. If they declare a namespace, set
the [`namespace` option](Configuration.md#options) so Barbarian can resolve the
fully-qualified class name.

## Why is my namespaced migration not found?

Set the `namespace` option (or the `namespace` key in `barbarian.json`) to match
the namespace your migration classes declare. The version name remains the
**unqualified** class name; the namespace is only used to load the class.

## What does `pending` mean in `barbarian status`?

The migration file was discovered but has no row in the version table yet — it
has never been applied. After `up` it becomes `up`; after `down` it becomes
`down`. See [CLI Commands](CLI-Commands.md#status--inspect-migrations).

## Why did `up` skip a migration?

`up` skips migrations already recorded as `Up`. To re-run one anyway, target it
explicitly — `up --version=...` forces a re-run — or call
[`upMigration($m, true)`](API-Reference.md#upmigration) from code.

## My `up()`/`down()` ran but nothing was recorded. Why?

A migration must **return `true`** for the runner to record the state change.
Returning `false` (or throwing) leaves the recorded status untouched so the
migration can be retried. See
[Writing Migrations](Writing-Migrations.md#return-values-matter).

## Can I change the version table name?

Yes — set `migrationTable` (in code) or `table_name` (in `barbarian.json`). The
name must match `^[A-Za-z_][A-Za-z0-9_]*$`.

## Does `create` need a database connection?

No. `create` only reads the configuration to know where (and under which
namespace) to write the file. You can scaffold migrations with the database
offline.

## Are migrations run inside a transaction?

No. Migrations are not wrapped in a transaction, and several engines (notably
MySQL) commit implicitly on DDL. Keep migrations small and make each `down()` a
faithful inverse of its `up()`.

## In what order do `up` and `down` run?

`up` (without `--version`) applies migrations in **ascending** file-name order.
`down` (without `--version`) reverts them in **reverse** order, so dependencies
are torn down after the things that depend on them.

## Why `--version=` and not `-version=`?

The CLI is built on [InitPHP Console](https://github.com/InitPHP/Console), where
`--long` tokens are *arguments*. Use the double-dash form:
`--version=...`, `--config=...`, `--dir=...`.

## How do I run the same thing from code instead of the CLI?

Build a [`Migrations`](API-Reference.md#migrations) and call
`upMigration()`/`downMigration()`. See [Programmatic Usage](Programmatic-Usage.md).

## Still stuck?

Open an issue at
[github.com/InitPHP/Barbarian/issues](https://github.com/InitPHP/Barbarian/issues/new)
or start a thread in
[Discussions](https://github.com/orgs/InitPHP/discussions).

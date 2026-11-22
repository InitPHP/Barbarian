# Upgrade from 1.x

Barbarian **2.0** is a modernisation and bug-fix release that contains breaking
changes. This page summarises what changed and how to adapt.

## At a glance

| Area | 1.x | 2.0 |
| --- | --- | --- |
| Minimum PHP | 7.4 | **8.1** in 2.x, **8.2** from 3.0.0 |
| `initphp/console` | `^1.0` | **`^2.1`** |
| CLI flags | `-version=` | **`--version=`** |
| `json` command | `json` | **`init`** |
| `Migrations` & query gateway | one class | **separate `QueryRunner`** |
| `getError()` | array | **renamed `getErrors()`** |
| `QueryInterface::query()` | `false\|PDOStatement` | **`PDOStatement`, throws** |

## Requirements

- Upgrade to **PHP 8.2+**.
- `composer require initphp/barbarian:^3.0` — this also pulls in
  `initphp/console:^2.1`.

## Breaking changes and how to adapt

### `getError()` → `getErrors()`

```diff
- foreach ($migrations->getError() as $error) { ... }
+ foreach ($migrations->getErrors() as $error) { ... }
```

### Migrations receive a `QueryRunner`, not the manager

`Migrations` no longer implements `QueryInterface`. The object passed to your
`up()`/`down()` is now a [`QueryRunner`](API-Reference.md#queryrunner) (still a
[`QueryInterface`](API-Reference.md#queryinterface)). If you only ever called
`$query->query(...)`, **no change is needed**. If you relied on the manager being
passed in, update accordingly.

### `query()` throws instead of returning `false`

The connection runs in `PDO::ERRMODE_EXCEPTION` mode and
[`query()`](API-Reference.md#queryinterface) returns a `PDOStatement`, throwing
`\PDOException` on failure. Remove any `=== false` checks:

```diff
- $stmt = $query->query($sql);
- if ($stmt === false) { /* handle */ }
+ $stmt = $query->query($sql); // throws on failure
```

### CLI flag syntax

Use double-dash arguments:

```diff
- vendor/bin/barbarian up -version=20221230091500
+ vendor/bin/barbarian up --version=20221230091500
```

### The `json` command is now `init`

```diff
- vendor/bin/barbarian json
+ vendor/bin/barbarian init
```

`init` also creates the `migrations/` folder referenced by the generated config.

## Bugs fixed in 2.0

If any of these bit you in 1.x, they are now resolved (and covered by tests):

- **Namespaced migrations are discovered.** The `namespace` option previously had
  no effect.
- **`down` actually runs.** The status comparison no longer breaks under drivers
  that return the column as a string (e.g. MySQL).
- **Selecting a migration by full class name no longer crashes** the CLI.
- **PostgreSQL works.** The version table DDL is valid (`SERIAL` primary key).
- **`table_name` in `barbarian.json` is honoured.**
- **`up()`/`down()` return values are honoured** — returning `false` no longer
  records a state change.
- **Bulk `down` runs in reverse order.**
- **`create` no longer needs a database connection.**
- **Invalid `barbarian.json` is reported** instead of slipping through.

## New in 2.0

- A [`status`](CLI-Commands.md#status--inspect-migrations) command, plus
  [`Migrations::status()`](API-Reference.md#migrations) and `getStatuses()`.
- A `force` argument on `downMigration()` (symmetric with `upMigration()`).
- Read accessors: `getFolder()`, `getNamespace()`, `getMigrationTable()`.
- A reusable console layer: [`Config`](API-Reference.md#config),
  [`ConfigLoader`](API-Reference.md#configloader),
  [`ManagerFactory`](API-Reference.md#managerfactory),
  [`MigrationTemplate`](API-Reference.md#migrationtemplate).

## See also

- [CLI Commands](CLI-Commands.md)
- [Programmatic Usage](Programmatic-Usage.md)
- [API Reference](API-Reference.md)

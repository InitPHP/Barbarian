# CLI Commands

The CLI entry point is installed at `vendor/bin/barbarian`. It is built on
[InitPHP Console](https://github.com/InitPHP/Console), so `barbarian help` lists
the commands and `barbarian <command> --help` shows per-command usage.

All commands except `init` read a [`barbarian.json`](Configuration.md#the-barbarianjson-file)
file; pass `--config=<path>` to point at a specific one (defaults to
`./barbarian.json`).

| Command | Connects to DB? | Purpose |
| --- | --- | --- |
| [`init`](#init--scaffold-the-config) | No | Scaffold `barbarian.json` and the migration folder. |
| [`create`](#create--scaffold-a-migration) | No | Scaffold a new migration class. |
| [`status`](#status--inspect-migrations) | Yes | Show each migration's state. |
| [`up`](#up--apply-migrations) | Yes | Apply migrations. |
| [`down`](#down--revert-migrations) | Yes | Revert migrations. |

## Argument syntax

Arguments use the double-dash `--name=value` form:

```bash
vendor/bin/barbarian up --version=20240101000000 --config=config/barbarian.json
```

> This is the InitPHP Console convention: `--long` tokens are **arguments**.
> Single-dash `-x` tokens are short *options* and are not used by Barbarian.

## `init` — scaffold the config

```bash
vendor/bin/barbarian init
vendor/bin/barbarian init --dir=config
```

Writes a `barbarian.json` template into the given directory (the current
directory by default) and creates the `migrations/` folder it points at. It
will not overwrite an existing configuration file. No database connection is
made.

| Argument | Default | Description |
| --- | --- | --- |
| `--dir` | current directory | Where to create `barbarian.json`. |

## `create` — scaffold a migration

```bash
vendor/bin/barbarian create
# -> Migration created: Migration_20240101000000
```

Creates a timestamped `Migration_<YmdHis>` class in the configured `folder`,
using the configured `namespace` when one is set. **No database connection is
opened**, so you can scaffold migrations even when the database is unavailable.

The generated class is empty (both `up()` and `down()` just `return true;`) —
fill in your statements. See [Writing Migrations](Writing-Migrations.md).

| Argument | Default | Description |
| --- | --- | --- |
| `--config` | `./barbarian.json` | Configuration file to read. |

## `status` — inspect migrations

```bash
vendor/bin/barbarian status
```

Lists every discovered migration with its recorded state and changes nothing:

```text
  [up]        Migration_20240101000000
  [down]      Migration_20240102000000
  [pending]   Migration_20240103000000
```

| State | Meaning |
| --- | --- |
| **up** | Applied. |
| **down** | Reverted. |
| **pending** | Discovered but never recorded in the version table. |

| Argument | Default | Description |
| --- | --- | --- |
| `--config` | `./barbarian.json` | Configuration file to read. |

## `up` — apply migrations

```bash
# Apply every discovered migration, in ascending order:
vendor/bin/barbarian up

# Apply a single migration by timestamp:
vendor/bin/barbarian up --version=20240101000000

# ...or by full class name:
vendor/bin/barbarian up --version=Migration_20240101000000
```

- Without `--version`, every migration is applied in ascending (file name)
  order. Already-applied migrations are skipped.
- With `--version`, the single matching migration is (re)applied. The value may
  be the full class name or just the timestamp portion.

| Argument | Default | Description |
| --- | --- | --- |
| `--version` | _(all)_ | A single migration to apply. |
| `--config` | `./barbarian.json` | Configuration file to read. |

## `down` — revert migrations

```bash
# Revert every migration, in reverse (descending) order:
vendor/bin/barbarian down

# Revert a single migration:
vendor/bin/barbarian down --version=20240101000000
vendor/bin/barbarian down --version=Migration_20240101000000
```

- Without `--version`, migrations are reverted **newest-first** — the reverse of
  how they were applied — which matters when later migrations depend on earlier
  ones.
- With `--version`, only the matching migration is reverted.

| Argument | Default | Description |
| --- | --- | --- |
| `--version` | _(all)_ | A single migration to revert. |
| `--config` | `./barbarian.json` | Configuration file to read. |

## Errors and exit behaviour

- A missing configuration file prints an error and a hint to run `barbarian init`.
- A malformed configuration file, a failed connection, or an exception thrown by
  a migration is caught and reported as an error message (no raw stack trace).
- Files in the folder that do not declare a valid migration class are reported as
  warnings and skipped (they still appear via [`getErrors()`](API-Reference.md#geterrors)).

## Next steps

- [Configuration](Configuration.md) — the `barbarian.json` keys.
- [Programmatic Usage](Programmatic-Usage.md) — the same actions from code.
- [Recipes](Recipes.md) — deployment and testing patterns.

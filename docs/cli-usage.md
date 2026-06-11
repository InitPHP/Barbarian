# Command-line usage

The CLI entry point is installed at `vendor/bin/barbarian`. It is built on
[InitPHP Console](https://github.com/InitPHP/Console), so `barbarian help` lists
the commands and `barbarian <command> --help` shows per-command usage.

All commands except `init` read a [`barbarian.json`](./configuration.md) file;
pass `--config=<path>` to point at a specific one (defaults to
`./barbarian.json`).

## `init` — scaffold the config

```bash
vendor/bin/barbarian init
vendor/bin/barbarian init --dir=config
```

Writes a `barbarian.json` template into the given directory (the current
directory by default) and creates the `migrations/` folder it points at. It will
not overwrite an existing configuration file. No database connection is made.

## `create` — scaffold a migration

```bash
vendor/bin/barbarian create
```

Creates a timestamped `Migration_<YmdHis>` class in the configured `folder`,
using the configured `namespace` when one is set. **No database connection is
opened**, so you can scaffold migrations even when the database is unavailable.

The generated class is empty (both `up()` and `down()` just `return true;`) —
fill in your statements.

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

- **up** — applied.
- **down** — reverted.
- **pending** — discovered but never recorded in the version table.

## `up` — apply migrations

```bash
# Apply every discovered migration, in ascending order:
vendor/bin/barbarian up

# Apply a single migration by timestamp:
vendor/bin/barbarian up --version=20240101000000

# ...or by full class name:
vendor/bin/barbarian up --version=Migration_20240101000000
```

- Without `--version`, every migration is applied in ascending (file name) order.
  Already-applied migrations are skipped.
- With `--version`, the single matching migration is (re)applied. The value may
  be the full class name or just the timestamp portion.

## `down` — revert migrations

```bash
# Revert every migration, in reverse (descending) order:
vendor/bin/barbarian down

# Revert a single migration:
vendor/bin/barbarian down --version=20240101000000
vendor/bin/barbarian down --version=Migration_20240101000000
```

- Without `--version`, migrations are reverted **newest-first** — the reverse of
  how they were applied — which is important when later migrations depend on
  earlier ones.
- With `--version`, only the matching migration is reverted.

## Argument syntax

Arguments use the double-dash, `--name=value` form:

```bash
vendor/bin/barbarian up --version=20240101000000 --config=config/barbarian.json
```

## Exit behaviour and errors

- A missing configuration file prints an error and a hint to run `barbarian init`.
- A malformed configuration file, a failed connection, or an exception thrown by
  a migration is caught and reported as an error message (no raw stack trace).
- Files in the folder that do not declare a valid migration class are reported as
  warnings and skipped.

## Next steps

- [Configuration](./configuration.md)
- [Programmatic usage](./programmatic-usage.md)

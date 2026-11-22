# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2026-11-22

### Changed (breaking)

- **Requires PHP `^8.2`.** Support for PHP 8.1 is dropped; CI now runs PHP 8.2, 8.3, 8.4 and 8.5.
- **Coding standard moved from PSR-12 (PHP_CodeSniffer) to PER-CS (php-cs-fixer)** through the shared `.php-cs-fixer.dist.php`; `phpcs.xml.dist` and `squizlabs/php_codesniffer` are removed and the whole code base was reformatted. The `cs`, `cs-ci` and `cs-fix` Composer scripts now run php-cs-fixer, and `cs-check` is added.

## [2.0.0] - 2026-06-11

A modernisation and bug-fix release. **Contains breaking changes.**

### Added
- `QueryRunner` — the default `QueryInterface`, a thin PDO wrapper that forces
  `PDO::ERRMODE_EXCEPTION` for a uniform error model.
- `MigrationStatus` backed enum (`Down = 0`, `Up = 1`).
- `Schema\Grammar` — driver-specific version table DDL and identifier quoting.
- Console layer: `Config`, `ConfigLoader`, `ManagerFactory`, `MigrationTemplate`
  and class-based commands (`InitCommand`, `CreateCommand`, `StatusCommand`,
  `UpCommand`, `DownCommand`).
- A `status` command (and `Migrations::status()` / `getStatuses()`) reporting
  each migration as up, down or pending.
- `--config` option for every command and a `--dir` option for `init`.
- `init` now also creates the migration folder referenced by the generated config.
- `force` argument for `downMigration()`.
- Read accessors `getFolder()`, `getNamespace()`, `getMigrationTable()`.
- Full PHPUnit test suite, PHPStan (level 6) analysis, PSR-12 coding standard and
  a GitHub Actions CI workflow (PHP 8.1–8.4).
- English documentation under `docs/`.

### Fixed
- Namespaced migrations were never discovered because the `namespace` option had
  no setter; it is now applied.
- `downMigration()` never ran because the status was compared with `===` against
  an `int` while PDO returns a string under most drivers; comparison is now
  type-safe.
- The CLI crashed when selecting a migration by full class name
  (`$migration[$version]` instead of `$migrations[$version]`).
- The PostgreSQL version table DDL was invalid (bogus `FOREIGN KEY`, no
  auto-incrementing key); it now uses `SERIAL`.
- The CLI `table_name` configuration key was ignored; it now maps to the version
  table name.
- `up()`/`down()` return values are now honoured — returning `false` no longer
  records a state change.
- Bulk `down` now runs in reverse (newest-first) order.
- `create` no longer requires a database connection.
- Invalid `barbarian.json` is reported instead of slipping through
  (`json_decode` returns `null`, not `false`).

### Changed
- **BREAKING:** minimum PHP version raised to **8.1**.
- **BREAKING:** requires `initphp/console` **^2.1**; the CLI was rewritten against
  the new API. Command flags now use the `--name=value` form (e.g. `--version=`).
- **BREAKING:** `Migrations` no longer implements `QueryInterface`; the query
  gateway is now a separate `QueryRunner` injected into migrations.
- **BREAKING:** `Migrations::getError()` renamed to `getErrors()`.
- **BREAKING:** `QueryInterface::query()` now returns `PDOStatement` and throws on
  failure instead of returning `false`.
- The `json` command was renamed to `init`.
- The version table now stores a unique `version_name` and a populated `last_time`.

### Migrating from 1.x
- Upgrade to PHP 8.1+ and run `composer require initphp/barbarian:^2.0`.
- Replace `getError()` calls with `getErrors()`.
- If you embedded the runner, note that migrations now receive a `QueryRunner`
  (still a `QueryInterface`) rather than the `Migrations` instance.
- Update CLI scripts to the `--version=` / `--config=` syntax and the `init`
  command name.

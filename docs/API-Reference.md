# API Reference

Every public class lives under the `InitPHP\Barbarian` namespace. This page
lists each type and its members with exact signatures.

| Class | Kind | Summary |
| --- | --- | --- |
| [`Migrations`](#migrations) | final class | Discovers migrations and runs them. |
| [`MigrationInterface`](#migrationinterface) | interface | The migration contract. |
| [`MigrationAbstract`](#migrationabstract) | abstract class | Base class implementing `getName()`. |
| [`QueryInterface`](#queryinterface) | interface | The query gateway. |
| [`QueryRunner`](#queryrunner) | final class | Default `QueryInterface` (PDO wrapper). |
| [`MigrationStatus`](#migrationstatus) | enum | `Down = 0`, `Up = 1`. |
| [`MigrationException`](#migrationexception) | class | Library-level failure. |
| [`Schema\Grammar`](#grammar) | final class | Driver-specific DDL & quoting. |
| [Console layer](#console-layer) | — | Config, factory, template, commands. |

---

## Migrations

`final class InitPHP\Barbarian\Migrations`

Discovers migration classes on disk, keeps the version table in sync and runs
the up()/down() side of a migration on demand.

### Constructor

```php
public function __construct(PDO $pdo, string $folder, array $options = [])
```

| Parameter | Type | Description |
| --- | --- | --- |
| `$pdo` | `PDO` | Connection; its error mode is forced to `ERRMODE_EXCEPTION`. |
| `$folder` | `string` | Directory holding the `Migration_*.php` files. |
| `$options` | `array` | `namespace` (`?string`) and `migrationTable` (`string`). Unknown keys ignored. |

**Throws** `InvalidArgumentException` when the folder does not exist or the table
name is invalid; [`MigrationException`](#migrationexception) when the version
table cannot be created or the folder cannot be read.

Constructing the instance ensures the version table exists and scans the folder.

### Members

| Method | Returns | Description |
| --- | --- | --- |
| [`getMigrations()`](#getmigrations) | `array<string, class-string>` | Discovered migrations, keyed by version name. |
| [`getErrors()`](#geterrors) | `array<int, string>` | Problems found while scanning. |
| `getFolder()` | `string` | Normalised folder (trailing separator). |
| `getNamespace()` | `?string` | Configured namespace, or null. |
| `getMigrationTable()` | `string` | Version table name. |
| [`upMigration()`](#upmigration) | `bool` | Apply a migration. |
| [`downMigration()`](#downmigration) | `bool` | Revert a migration. |
| `status()` | `?MigrationStatus` | Recorded status of one migration. |
| `getStatuses()` | `array<string, ?MigrationStatus>` | Status of every discovered migration. |

#### getMigrations()

```php
public function getMigrations(): array
```

Returns the discovered migrations as `array<string, class-string<MigrationInterface>>`
— keyed by version name (the unqualified class name) and mapped to the
fully-qualified class name. Only classes implementing
[`MigrationInterface`](#migrationinterface) are included.

#### getErrors()

```php
public function getErrors(): array
```

Returns `array<int, string>` of human-readable problems found while scanning the
folder — for example a `Migration_*.php` file that does not declare the expected
class, or one whose class does not implement `MigrationInterface`.

#### upMigration()

```php
public function upMigration(MigrationInterface $migration, bool $force = false): bool
```

Applies a migration's `up()` side and records it as `MigrationStatus::Up`. A
migration already at `Up` is skipped unless `$force` is true. When `up()` returns
`false` the state is left untouched. Returns `true` when `up()` ran and the state
was recorded. **Throws** `\PDOException` on a database error.

#### downMigration()

```php
public function downMigration(MigrationInterface $migration, bool $force = false): bool
```

Reverts a migration's `down()` side and records it as `MigrationStatus::Down`. A
migration that was never applied, or already at `Down` (unless `$force`), is
skipped. When `down()` returns `false` the state is left untouched. Returns
`true` when `down()` ran and the state was recorded. **Throws** `\PDOException`
on a database error.

#### status() / getStatuses()

```php
public function status(MigrationInterface|string $migration): ?MigrationStatus
public function getStatuses(): array
```

`status()` returns the recorded [`MigrationStatus`](#migrationstatus) of a
migration (by instance or version name), or `null` when it has never been
applied. `getStatuses()` returns `array<string, MigrationStatus|null>` for every
discovered migration, keyed by version name.

---

## MigrationInterface

`interface InitPHP\Barbarian\MigrationInterface`

```php
public function up(QueryInterface $query): bool;
public function down(QueryInterface $query): bool;
public function getName(): string;
```

| Method | Description |
| --- | --- |
| `up()` | Applies the migration. Return `true` on success; `false` (or throw) to abort recording. |
| `down()` | Reverts the migration. Same return semantics as `up()`. |
| `getName()` | The unique version name the migration is tracked under. |

---

## MigrationAbstract

`abstract class InitPHP\Barbarian\MigrationAbstract implements MigrationInterface`

Implements `getName()` for you; subclasses implement only `up()` and `down()`.

```php
abstract public function up(QueryInterface $query): bool;
abstract public function down(QueryInterface $query): bool;
final public function getName(): string;
```

`getName()` returns the class name **without** its namespace prefix, so
`App\Migrations\Migration_20240101000000` is tracked as
`Migration_20240101000000`.

---

## QueryInterface

`interface InitPHP\Barbarian\QueryInterface`

The query gateway handed to a migration's `up()`/`down()`.

```php
public function query(string $sql, ?array $arguments = null): \PDOStatement;
```

| Parameter | Type | Description |
| --- | --- | --- |
| `$sql` | `string` | The statement to run. |
| `$arguments` | `array<int\|string, mixed>\|null` | Bound parameters, or null. |

**Returns** the executed `PDOStatement`. **Throws** `\PDOException` when the
statement cannot be prepared or executed.

---

## QueryRunner

`final class InitPHP\Barbarian\QueryRunner implements QueryInterface`

The default `QueryInterface`: a thin wrapper around a PDO connection.

```php
public function __construct(PDO $pdo)
public function pdo(): PDO
public function driver(): string
public function query(string $sql, ?array $arguments = null): \PDOStatement
```

| Member | Description |
| --- | --- |
| `__construct()` | Wraps a connection and forces `PDO::ERRMODE_EXCEPTION`. |
| `pdo()` | The underlying PDO connection. |
| `driver()` | The PDO driver name (`mysql`, `sqlite`, `pgsql`, …). |
| `query()` | See [`QueryInterface::query()`](#queryinterface). |

---

## MigrationStatus

`enum InitPHP\Barbarian\MigrationStatus: int`

The persisted state of a migration in the version table.

| Case | Value | Meaning |
| --- | --- | --- |
| `Down` | `0` | `down()` was the last method applied. |
| `Up` | `1` | `up()` was the last method applied. |

The backing integer is exactly what is stored in (and read back from) the
`status` column.

---

## MigrationException

`class InitPHP\Barbarian\MigrationException extends \Exception`

Raised for failures originating in Barbarian itself — an unreadable migration
folder, an invalid configuration file, a failed connection — as opposed to
`\PDOException`s raised while executing migration statements. See
[Exceptions](Exceptions.md).

---

## Grammar

`final class InitPHP\Barbarian\Schema\Grammar`

Produces the small amount of driver-specific SQL the version table needs.
MySQL/MariaDB, SQLite and PostgreSQL are handled explicitly; any other driver
falls back to the MySQL grammar.

```php
public function __construct(string $driver)
public function quoteIdentifier(string $identifier): string
public function createVersionTable(string $table): string
```

| Member | Description |
| --- | --- |
| `__construct()` | `$driver` is a PDO driver name such as `mysql`, `sqlite` or `pgsql`. |
| `quoteIdentifier()` | Wraps an identifier in the driver's quoting characters. |
| `createVersionTable()` | The `CREATE TABLE IF NOT EXISTS` statement for the version table. |

See [Database Drivers](Database-Drivers.md) for the generated DDL.

---

## Console layer

`namespace InitPHP\Barbarian\Console`

These types power the CLI and can be reused in your own tooling (see
[Extending](Extending.md)).

### Config

`final class Config`

An immutable, validated representation of a `barbarian.json` file.

```php
public function __construct(
    public readonly string $dsn,
    public readonly string $username,
    public readonly string $password,
    public readonly string $folder,
    public readonly string $migrationTable,
    public readonly ?string $namespace
)
```

### ConfigLoader

`final class ConfigLoader`

Reads and validates configuration into a [`Config`](#config). Both `table_name`
and `migrationTable` are accepted for the version table name.

```php
public static function fromFile(string $path): Config
public static function fromJson(string $json, string $source = 'configuration'): Config
```

Required keys: `dsn`, `username`, `password`, `folder`. **Throws**
[`MigrationException`](#migrationexception) when the input cannot be read, is not
valid JSON, or is missing a required key.

### ManagerFactory

`final class ManagerFactory`

Builds a configured [`Migrations`](#migrations) (and the PDO connection it needs)
from a [`Config`](#config).

```php
public static function fromConfig(Config $config): Migrations
```

**Throws** [`MigrationException`](#migrationexception) when the connection cannot
be opened.

### MigrationTemplate

`final class MigrationTemplate`

Renders the PHP source for a new, empty migration class.

```php
public static function render(string $className, ?string $namespace): string
```

### Commands

Class-based [InitPHP Console](https://github.com/InitPHP/Console) commands. Each
sets a `$command` name and implements `execute()`.

| Class | Command | Arguments |
| --- | --- | --- |
| `InitCommand` | `init` | `--dir` |
| `CreateCommand` | `create` | `--config` |
| `StatusCommand` | `status` | `--config` |
| `UpCommand` | `up` | `--version`, `--config` |
| `DownCommand` | `down` | `--version`, `--config` |

The configuration-aware commands extend `BarbarianCommand`, which provides the
shared `--config` argument and the `loadConfig()` helper. See
[CLI Commands](CLI-Commands.md) for usage and [Extending](Extending.md) for
registering them in your own application.

---

## See also

- [Writing Migrations](Writing-Migrations.md)
- [Programmatic Usage](Programmatic-Usage.md)
- [Exceptions](Exceptions.md)

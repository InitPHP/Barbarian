# Configuration

Barbarian is configured in two places: the [`Migrations`](API-Reference.md#migrations)
constructor (when used from code) and the `barbarian.json` file (when used
through the CLI).

## The `Migrations` constructor

```php
use InitPHP\Barbarian\Migrations;

$migrations = new Migrations($pdo, $folder, $options);
```

| Argument | Type | Description |
| --- | --- | --- |
| `$pdo` | `PDO` | An open connection. Its error mode is forced to `ERRMODE_EXCEPTION`. |
| `$folder` | `string` | Directory containing the `Migration_*.php` files. **Must exist.** |
| `$options` | `array` | Optional settings (see below). |

### Options

| Key | Type | Default | Description |
| --- | --- | --- | --- |
| `namespace` | `?string` | `null` | Namespace the migration classes live under. |
| `migrationTable` | `string` | `migrations_versions` | Name of the version table. |

Unknown keys are ignored. The table name must match
`^[A-Za-z_][A-Za-z0-9_]*$` (letters, digits and underscores, not starting with
a digit); anything else throws an `InvalidArgumentException`.

```php
$migrations = new Migrations($pdo, __DIR__ . '/migrations', [
    'namespace'      => 'App\\Migrations', // null for the global namespace
    'migrationTable' => 'schema_versions',
]);
```

You can read the resolved configuration back:

```php
$migrations->getFolder();         // e.g. "/app/migrations/"
$migrations->getNamespace();      // "App\Migrations" or null
$migrations->getMigrationTable(); // "schema_versions"
```

> **Side effects of construction.** Building a `Migrations` instance ensures
> the version table exists (creating it if necessary) and scans the folder for
> migration classes.

## The `barbarian.json` file

The CLI reads its settings from a JSON file (default: `./barbarian.json`).
Generate a template with [`init`](CLI-Commands.md#init--scaffold-the-config):

```bash
vendor/bin/barbarian init
```

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

| Key | Required | Maps to | Description |
| --- | --- | --- | --- |
| `dsn` | yes | PDO DSN | Connection string. |
| `username` | yes | PDO username | Database user. |
| `password` | yes | PDO password | Database password (may be an empty string). |
| `folder` | yes | `$folder` | Migration directory. |
| `table_name` | no | `migrationTable` option | Version table name. `migrationTable` is also accepted. |
| `namespace` | no | `namespace` option | Migration namespace, or `null`. |

> Both `table_name` (CLI-friendly) and `migrationTable` (library-native) are
> accepted for the version table name. An empty `namespace` string is treated
> as `null`.

### Connection strings (DSN)

| Database | Example DSN |
| --- | --- |
| MySQL / MariaDB | `mysql:host=localhost;dbname=test;charset=utf8mb4` |
| SQLite (file) | `sqlite:/var/www/app/database/app.db` |
| SQLite (memory) | `sqlite::memory:` |
| PostgreSQL | `pgsql:host=localhost;port=5432;dbname=test` |

See [Database Drivers](Database-Drivers.md) for the version table schema each
driver uses.

### Choosing the config file

Every configuration-aware command accepts `--config`:

```bash
vendor/bin/barbarian up --config=config/barbarian.json
```

When omitted, `barbarian.json` in the current working directory is used. A
missing file produces a clear error; an unreadable or malformed file raises a
[`MigrationException`](Exceptions.md#migrationexception).

## Loading configuration from code

The CLI's configuration handling is reusable:

```php
use InitPHP\Barbarian\Console\ConfigLoader;
use InitPHP\Barbarian\Console\ManagerFactory;

$config = ConfigLoader::fromFile(__DIR__ . '/barbarian.json');
$migrations = ManagerFactory::fromConfig($config);
```

See [`ConfigLoader`](API-Reference.md#configloader) and
[`ManagerFactory`](API-Reference.md#managerfactory).

## Next steps

- [CLI Commands](CLI-Commands.md)
- [Programmatic Usage](Programmatic-Usage.md)
- [Database Drivers](Database-Drivers.md)

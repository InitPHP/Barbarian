# Configuration

Barbarian is configured in two places: the `Migrations` constructor (when used
programmatically) and the `barbarian.json` file (when used through the CLI).

## The `Migrations` constructor

```php
use InitPHP\Barbarian\Migrations;

$migrations = new Migrations($pdo, $folder, $options);
```

| Argument   | Type     | Description                                                              |
|------------|----------|--------------------------------------------------------------------------|
| `$pdo`     | `PDO`    | An open connection. Its error mode is forced to `ERRMODE_EXCEPTION`.     |
| `$folder`  | `string` | Directory containing the `Migration_*.php` files. Must exist.            |
| `$options` | `array`  | Optional settings (see below).                                           |

### Options

| Key              | Type          | Default                  | Description                                         |
|------------------|---------------|--------------------------|-----------------------------------------------------|
| `namespace`      | `?string`     | `null`                   | Namespace the migration classes live under.         |
| `migrationTable` | `string`      | `migrations_versions`    | Name of the version table.                          |

Unknown keys are ignored. The table name must match `^[A-Za-z_][A-Za-z0-9_]*$`
(letters, digits and underscores, not starting with a digit); anything else
throws an `InvalidArgumentException`.

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

## The `barbarian.json` file

The CLI reads its settings from a JSON file (default: `./barbarian.json`).
Generate a template with:

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

| Key            | Required | Maps to                | Description                                            |
|----------------|----------|------------------------|--------------------------------------------------------|
| `dsn`          | yes      | PDO DSN                | Connection string.                                     |
| `username`     | yes      | PDO username           | Database user.                                         |
| `password`     | yes      | PDO password           | Database password (may be an empty string).            |
| `folder`       | yes      | `$folder`              | Migration directory.                                   |
| `table_name`   | no       | `migrationTable` option| Version table name. `migrationTable` is also accepted. |
| `namespace`    | no       | `namespace` option     | Migration namespace, or `null`.                        |

> **Note:** Both `table_name` (CLI-friendly) and `migrationTable`
> (library-native) are accepted for the version table name. An empty
> `namespace` string is treated as `null`.

### Choosing the config file

Every configuration-aware command accepts `--config`:

```bash
vendor/bin/barbarian up --config=config/barbarian.json
```

When omitted, `barbarian.json` in the current working directory is used. A
missing file produces a clear error; an unreadable or malformed file raises a
`MigrationException`.

## Next steps

- [Command-line usage](./cli-usage.md)
- [Programmatic usage](./programmatic-usage.md)

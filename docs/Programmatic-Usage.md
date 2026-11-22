# Programmatic Usage

You can drive Barbarian directly from PHP, without the CLI — useful for deploy
scripts, test bootstrapping or framework integration.

## Creating the manager

```php
require 'vendor/autoload.php';

use InitPHP\Barbarian\Migrations;

$pdo = new PDO('sqlite:' . __DIR__ . '/app.db');

$migrations = new Migrations($pdo, __DIR__ . '/migrations', [
    'namespace'      => 'App\\Migrations',
    'migrationTable' => 'migrations_versions',
]);
```

Constructing the manager has two side effects:

1. It ensures the version table exists (creating it if necessary).
2. It scans the folder and discovers the migration classes.

## Discovering migrations

```php
// version name (short class name) => fully-qualified class name
$map = $migrations->getMigrations();

foreach ($map as $version => $class) {
    echo $version, ' => ', $class, PHP_EOL;
}

// Problems found while scanning (e.g. files without a valid class):
foreach ($migrations->getErrors() as $error) {
    fwrite(STDERR, $error . PHP_EOL);
}
```

A file is reported in [`getErrors()`](API-Reference.md#geterrors) (and omitted from
[`getMigrations()`](API-Reference.md#getmigrations)) when it does not declare a
class implementing [`MigrationInterface`](API-Reference.md#migrationinterface).

## Applying and reverting

```php
use App\Migrations\Migration_20240101000000;

$migration = new Migration_20240101000000();

$migrations->upMigration($migration);   // apply
$migrations->downMigration($migration); // revert
```

Both methods return `bool`:

- [`upMigration()`](API-Reference.md#upmigration) returns `true` when `up()` ran and
  the state was recorded; `false` when the migration was already up (and not
  forced) or `up()` returned `false`.
- [`downMigration()`](API-Reference.md#downmigration) returns `true` when `down()`
  ran and the state was recorded; `false` when the migration was never applied,
  is already down (and not forced), or `down()` returned `false`.

### Running everything

```php
foreach ($migrations->getMigrations() as $class) {
    $migrations->upMigration(new $class());
}
```

To revert everything safely, iterate in reverse:

```php
foreach (array_reverse($migrations->getMigrations()) as $class) {
    $migrations->downMigration(new $class());
}
```

### Forcing a re-run

Both methods accept a second `bool $force` argument to run again even when the
migration is already in the target state:

```php
$migrations->upMigration($migration, true);   // re-run up()
$migrations->downMigration($migration, true); // re-run down()
```

## Reading status

```php
use InitPHP\Barbarian\MigrationStatus;

// A single migration (by instance or version name); null = never applied:
$migrations->status($migration);                 // ?MigrationStatus
$migrations->status('Migration_20240101000000'); // ?MigrationStatus

// Every discovered migration, keyed by version name:
foreach ($migrations->getStatuses() as $version => $status) {
    echo $version, ': ', $status?->name ?? 'pending', PHP_EOL;
}
```

This is exactly what the [`status`](CLI-Commands.md#status--inspect-migrations) CLI
command renders.

## The status enum

The recorded state is modelled by [`MigrationStatus`](API-Reference.md#migrationstatus):

```php
use InitPHP\Barbarian\MigrationStatus;

MigrationStatus::Down->value; // 0
MigrationStatus::Up->value;   // 1
```

These integers are exactly what is stored in the version table's `status`
column; comparisons are made on the integer value, so the library does not rely
on the PDO driver returning a particular PHP type.

## Building the manager from a config file

```php
use InitPHP\Barbarian\Console\ConfigLoader;
use InitPHP\Barbarian\Console\ManagerFactory;

$config = ConfigLoader::fromFile(__DIR__ . '/barbarian.json');
$migrations = ManagerFactory::fromConfig($config);
```

## Error handling

| Failure | Exception |
| --- | --- |
| Missing folder, unreadable/invalid config, failed connection | [`MigrationException`](Exceptions.md#migrationexception) |
| Invalid table name | `InvalidArgumentException` |
| Database error while executing a statement | `\PDOException` |

See [Exceptions](Exceptions.md) for the full picture.

## Next steps

- [Writing Migrations](Writing-Migrations.md)
- [Extending](Extending.md)
- [API Reference](API-Reference.md)

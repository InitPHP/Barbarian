# Programmatic usage

You can drive Barbarian directly from PHP, without the CLI — useful for
deploy scripts, test bootstrapping or framework integration.

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

## Applying and reverting

```php
use App\Migrations\Migration_20240101000000;

$migration = new Migration_20240101000000();

$migrations->upMigration($migration);   // apply
$migrations->downMigration($migration); // revert
```

Both methods return `bool`:

- `upMigration()` returns `true` when `up()` ran and the state was recorded;
  `false` when the migration was already up (and not forced) or `up()` returned
  `false`.
- `downMigration()` returns `true` when `down()` ran and the state was recorded;
  `false` when the migration was never applied, is already down (and not forced),
  or `down()` returned `false`.

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

## Status

The recorded state is modelled by the `InitPHP\Barbarian\MigrationStatus`
backed enum:

```php
use InitPHP\Barbarian\MigrationStatus;

MigrationStatus::Down->value; // 0
MigrationStatus::Up->value;   // 1
```

These integers are exactly what is stored in the version table's `status`
column; comparisons are made on the integer value, so the library does not rely
on the PDO driver returning a particular PHP type.

### Querying status

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

This is exactly what the [`status` CLI command](./cli-usage.md#status--inspect-migrations) renders.

## Building the manager from a config file

If you want the CLI's configuration handling in your own code, reuse the console
helpers:

```php
use InitPHP\Barbarian\Console\ConfigLoader;
use InitPHP\Barbarian\Console\ManagerFactory;

$config = ConfigLoader::fromFile(__DIR__ . '/barbarian.json');
$migrations = ManagerFactory::fromConfig($config);
```

## Error handling

- Configuration/discovery problems (missing folder, unreadable config, failed
  connection) throw `InitPHP\Barbarian\MigrationException`.
- A bad table name throws `InvalidArgumentException`.
- Database errors while executing statements throw `\PDOException`.

## Next steps

- [Writing migrations](./writing-migrations.md)
- [Extending Barbarian](./extending.md)

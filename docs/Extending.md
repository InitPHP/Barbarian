# Extending

Barbarian is deliberately small, but its pieces are separated so you can reuse
or replace them.

## The building blocks

| Class / interface | Responsibility |
| --- | --- |
| [`QueryInterface`](API-Reference.md#queryinterface) | Contract handed to migrations to run statements. |
| [`QueryRunner`](API-Reference.md#queryrunner) | Default `QueryInterface`: a thin PDO wrapper. |
| [`Migrations`](API-Reference.md#migrations) | Discovers migrations, keeps the version table in sync. |
| [`MigrationStatus`](API-Reference.md#migrationstatus) | Enum: `Down = 0`, `Up = 1`. |
| [`Schema\Grammar`](API-Reference.md#grammar) | Driver-specific version table DDL and identifier quoting. |
| [`Console\Config`](API-Reference.md#config) | Immutable, validated `barbarian.json` representation. |
| [`Console\ConfigLoader`](API-Reference.md#configloader) | Parses a config file/string into a `Config`. |
| [`Console\ManagerFactory`](API-Reference.md#managerfactory) | Builds a `Migrations` (and its PDO) from a `Config`. |
| [`Console\MigrationTemplate`](API-Reference.md#migrationtemplate) | Renders the source of a new migration class. |
| `Console\*Command` | The `init`, `create`, `status`, `up` and `down` commands. |

## Using the query runner directly

[`QueryRunner`](API-Reference.md#queryrunner) is a standalone PDO helper you can use
anywhere:

```php
use InitPHP\Barbarian\QueryRunner;

$runner = new QueryRunner($pdo);   // forces PDO::ERRMODE_EXCEPTION
$runner->driver();                  // 'mysql' | 'sqlite' | 'pgsql' | ...
$statement = $runner->query('SELECT * FROM users WHERE id = :id', [':id' => 1]);
$user = $statement->fetch(PDO::FETCH_ASSOC);
```

Because the manager type-hints [`QueryInterface`](API-Reference.md#queryinterface),
you can also provide your own implementation (for logging, metrics, dry-runs, …)
and pass it to your migrations when invoking them yourself.

## Loading configuration in your own tools

```php
use InitPHP\Barbarian\Console\ConfigLoader;
use InitPHP\Barbarian\Console\ManagerFactory;

// From a file:
$config = ConfigLoader::fromFile('barbarian.json');

// ...or from a JSON string (e.g. an env var):
$config = ConfigLoader::fromJson(getenv('BARBARIAN_CONFIG') ?: '{}', 'env');

$migrations = ManagerFactory::fromConfig($config);
```

[`ConfigLoader`](API-Reference.md#configloader) validates the required keys
(`dsn`, `username`, `password`, `folder`) and throws a
[`MigrationException`](Exceptions.md#migrationexception) on malformed input.

## Registering the commands in your own CLI

The console commands are ordinary `InitPHP\Console\Command` subclasses, so you
can register them in your own console application:

```php
use InitPHP\Console\Application;
use InitPHP\Barbarian\Console\CreateCommand;
use InitPHP\Barbarian\Console\DownCommand;
use InitPHP\Barbarian\Console\InitCommand;
use InitPHP\Barbarian\Console\StatusCommand;
use InitPHP\Barbarian\Console\UpCommand;

$app = new Application('My Tool', '1.0.0');
$app->register(InitCommand::class);
$app->register(CreateCommand::class);
$app->register(StatusCommand::class);
$app->register(UpCommand::class);
$app->register(DownCommand::class);
$app->run();
```

This is exactly what the bundled `barbarian` binary does.

## Generating migration source

```php
use InitPHP\Barbarian\Console\MigrationTemplate;

$code = MigrationTemplate::render('Migration_20240101000000', 'App\\Migrations');
file_put_contents('migrations/Migration_20240101000000.php', $code);
```

## Framework integration

Because the whole flow is just *“build a `Migrations`, then call
`upMigration()`/`downMigration()`”*, integrating with a framework is mostly a
matter of constructing the manager from the framework's PDO connection and
wiring a few console commands. See [Recipes](Recipes.md) for concrete patterns.

## See also

- [Programmatic Usage](Programmatic-Usage.md)
- [Database Drivers](Database-Drivers.md)
- [API Reference](API-Reference.md)

# Extending Barbarian

Barbarian is deliberately small, but its pieces are separated so you can reuse
or replace them.

## The building blocks

| Class / interface                              | Responsibility                                              |
|------------------------------------------------|-------------------------------------------------------------|
| `InitPHP\Barbarian\QueryInterface`             | Contract handed to migrations to run statements.            |
| `InitPHP\Barbarian\QueryRunner`                | Default `QueryInterface`: a thin PDO wrapper.               |
| `InitPHP\Barbarian\Migrations`                 | Discovers migrations, keeps the version table in sync.      |
| `InitPHP\Barbarian\MigrationStatus`            | Enum: `Down = 0`, `Up = 1`.                                 |
| `InitPHP\Barbarian\Schema\Grammar`             | Driver-specific version table DDL and identifier quoting.   |
| `InitPHP\Barbarian\Console\Config`             | Immutable, validated `barbarian.json` representation.       |
| `InitPHP\Barbarian\Console\ConfigLoader`       | Parses a config file/string into a `Config`.                |
| `InitPHP\Barbarian\Console\ManagerFactory`     | Builds a `Migrations` (and its PDO) from a `Config`.        |
| `InitPHP\Barbarian\Console\MigrationTemplate`  | Renders the source of a new migration class.                |
| `InitPHP\Barbarian\Console\*Command`           | The `init`, `create`, `up` and `down` console commands.     |

## Using the query runner directly

`QueryRunner` is a standalone PDO helper you can use anywhere:

```php
use InitPHP\Barbarian\QueryRunner;

$runner = new QueryRunner($pdo);   // forces PDO::ERRMODE_EXCEPTION
$runner->driver();                  // 'mysql' | 'sqlite' | 'pgsql' | ...
$statement = $runner->query('SELECT * FROM users WHERE id = :id', [':id' => 1]);
$user = $statement->fetch(PDO::FETCH_ASSOC);
```

Because the manager type-hints `QueryInterface`, you can also provide your own
implementation (for logging, metrics, dry-runs, …) and pass it to your
migrations when invoking them yourself.

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

`ConfigLoader` validates the required keys (`dsn`, `username`, `password`,
`folder`) and throws a `MigrationException` on malformed input.

## Registering the commands elsewhere

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

## Next steps

- [Programmatic usage](./programmatic-usage.md)
- [Database drivers](./drivers.md)

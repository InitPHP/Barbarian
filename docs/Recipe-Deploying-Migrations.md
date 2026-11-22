# Recipe: Deploying Migrations

A few patterns for running migrations as part of a deploy or CI pipeline.

## The simplest deploy step

If your project ships a `barbarian.json`, the deploy step is one command:

```bash
vendor/bin/barbarian up
```

`up` applies every pending migration in order and is safe to run repeatedly —
already-applied migrations are skipped.

## Inspect before applying

Print the plan first so the deploy log shows exactly what will change:

```bash
vendor/bin/barbarian status
vendor/bin/barbarian up
```

## A guarded deploy script

When you want a non-zero exit code on failure and a friendly message instead of
a stack trace:

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use InitPHP\Barbarian\Console\ConfigLoader;
use InitPHP\Barbarian\Console\ManagerFactory;
use InitPHP\Barbarian\MigrationException;

try {
    $config = ConfigLoader::fromFile(__DIR__ . '/barbarian.json');
    $migrations = ManagerFactory::fromConfig($config);

    foreach ($migrations->getErrors() as $error) {
        fwrite(STDERR, "warning: {$error}\n");
    }

    foreach ($migrations->getMigrations() as $version => $class) {
        if ($migrations->upMigration(new $class())) {
            echo "applied: {$version}\n";
        }
    }
} catch (MigrationException | PDOException $e) {
    fwrite(STDERR, "migration failed: {$e->getMessage()}\n");
    exit(1);
}

echo "migrations up to date\n";
```

## In GitHub Actions

```yaml
- name: Run migrations
  env:
    BARBARIAN_CONFIG_PATH: config/barbarian.json
  run: vendor/bin/barbarian up --config="$BARBARIAN_CONFIG_PATH"
```

Provide the production credentials through the workflow's secrets and a config
file generated at deploy time, or commit a `barbarian.json` whose `dsn`,
`username` and `password` reference environment-specific values.

## Rolling back

To revert the most recent change during an incident:

```bash
vendor/bin/barbarian down --version=Migration_20240101000000
```

To tear an environment all the way down (reverse order):

```bash
vendor/bin/barbarian down
```

> Migrations are not wrapped in a transaction, and several databases (notably
> MySQL) commit implicitly on DDL statements. Design each migration's `down()`
> to be a faithful inverse of its `up()`, and prefer small, focused migrations.

## See also

- [CLI Commands](CLI-Commands.md)
- [Recipe: Testing Migrations](Recipe-Testing-Migrations.md)
- [Exceptions](Exceptions.md)

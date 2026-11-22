# Recipes

Practical, copy-pasteable patterns for common tasks. Each recipe is grounded in
the real API.

| Recipe | What it shows |
| --- | --- |
| [Testing Migrations](Recipe-Testing-Migrations.md) | Running migrations against in-memory SQLite in your test suite. |
| [Deploying Migrations](Recipe-Deploying-Migrations.md) | Applying migrations safely during a deploy or in CI. |

## Quick patterns

### Apply everything, then verify

```php
$migrations = new InitPHP\Barbarian\Migrations($pdo, __DIR__ . '/migrations');

foreach ($migrations->getMigrations() as $class) {
    $migrations->upMigration(new $class());
}

foreach ($migrations->getStatuses() as $version => $status) {
    printf("%-12s %s\n", $status?->name ?? 'pending', $version);
}
```

### Revert the last applied migration

```php
$applied = array_filter(
    $migrations->getStatuses(),
    static fn ($status) => $status === InitPHP\Barbarian\MigrationStatus::Up
);

if ($applied !== []) {
    $version = array_key_last($applied);
    $class = $migrations->getMigrations()[$version];
    $migrations->downMigration(new $class());
}
```

### Fail a deploy when a migration errors

```php
foreach ($migrations->getMigrations() as $class) {
    if (!$migrations->upMigration(new $class())) {
        // already up, or up() returned false — decide per your policy
        continue;
    }
}
```

> Remember: a thrown `\PDOException` from a migration aborts the loop. Wrap the
> loop in a `try/catch` if you want to convert it into a non-zero exit code with
> a friendly message. See [Exceptions](Exceptions.md).

## See also

- [Programmatic Usage](Programmatic-Usage.md)
- [Extending](Extending.md)

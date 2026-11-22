# Exceptions

Barbarian uses three exception types, each signalling a different layer of
failure.

| Exception | Layer | Typical cause |
| --- | --- | --- |
| [`MigrationException`](#migrationexception) | Barbarian | Bad config, unreadable folder, failed connection. |
| [`InvalidArgumentException`](#invalidargumentexception) | Barbarian | Invalid folder or table name passed to `Migrations`. |
| [`\PDOException`](#pdoexception) | PDO | A statement failed to prepare or execute. |

## MigrationException

`class InitPHP\Barbarian\MigrationException extends \Exception`

Raised for failures originating in Barbarian itself, rather than while executing
a migration's SQL. It is thrown when:

- the migration folder exists but its contents cannot be read
  ([`Migrations`](API-Reference.md#migrations) construction);
- the version table cannot be created;
- a `barbarian.json` file cannot be read, is not valid JSON, or is missing a
  required key ([`ConfigLoader`](API-Reference.md#configloader));
- the database connection cannot be opened ([`ManagerFactory`](API-Reference.md#managerfactory)).

```php
use InitPHP\Barbarian\Console\ConfigLoader;
use InitPHP\Barbarian\MigrationException;

try {
    $config = ConfigLoader::fromFile('barbarian.json');
} catch (MigrationException $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
```

## InvalidArgumentException

The standard SPL `\InvalidArgumentException` is thrown by the
[`Migrations`](API-Reference.md#migrations) constructor when:

- the migration `$folder` does not exist; or
- the `migrationTable` name does not match `^[A-Za-z_][A-Za-z0-9_]*$`.

```php
use InitPHP\Barbarian\Migrations;

try {
    new Migrations($pdo, '/does/not/exist');
} catch (InvalidArgumentException $e) {
    // "The migration folder could not be found: /does/not/exist"
}
```

## \PDOException

Thrown by PDO (surfaced through [`QueryRunner`](API-Reference.md#queryrunner)) when
a statement fails to prepare or execute — both for the library's own bookkeeping
queries and for the statements inside your migrations. The runner forces
`PDO::ERRMODE_EXCEPTION`, so failures are never silently swallowed.

```php
public function up(QueryInterface $query): bool
{
    // A syntax error or constraint violation here throws \PDOException,
    // which aborts the run and (under the CLI) is reported as an error.
    $query->query('CREATE TABLE ...');

    return true;
}
```

## On the command line

Every command runs inside a `try/catch` in the Console application: any thrown
`\Throwable` (including all of the above) is caught and printed as a single
`[ERROR] <message>` line — no raw stack trace reaches the user.

## See also

- [Programmatic Usage → Error handling](Programmatic-Usage.md#error-handling)
- [API Reference](API-Reference.md)

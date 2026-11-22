# Database Drivers

Barbarian talks to your database through PDO, so any PDO driver can run your
migration statements. The only driver-specific SQL the library itself generates
is the **version table** DDL, produced by [`Schema\Grammar`](API-Reference.md#grammar)
based on the connection's `PDO::ATTR_DRIVER_NAME`.

Three drivers are handled explicitly; any other driver falls back to the MySQL
grammar.

| Driver name (`PDO::ATTR_DRIVER_NAME`) | Grammar |
| --- | --- |
| `mysql` | MySQL/MariaDB |
| `sqlite` | SQLite |
| `pgsql` | PostgreSQL |
| _anything else_ | MySQL (fallback) |

## Version table schema

The version table tracks every known migration with these columns:

| Column | Purpose |
| --- | --- |
| `id` | Auto-incrementing primary key. |
| `version_name` | The migration's version name (unique). |
| `status` | `0` = down, `1` = up (see [`MigrationStatus`](API-Reference.md#migrationstatus)). |
| `last_time` | When the row last changed. |

### MySQL / MariaDB (`mysql`)

```sql
CREATE TABLE IF NOT EXISTS `migrations_versions` (
    id INT NOT NULL AUTO_INCREMENT,
    version_name VARCHAR(255) NOT NULL,
    status INT NOT NULL,
    last_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_version_name (version_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

```php
$pdo = new PDO('mysql:host=localhost;dbname=test;charset=utf8mb4', 'root', '');
```

### SQLite (`sqlite`)

```sql
CREATE TABLE IF NOT EXISTS "migrations_versions" (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    version_name TEXT NOT NULL UNIQUE,
    status INTEGER NOT NULL,
    last_time TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

```php
$pdo = new PDO('sqlite:' . __DIR__ . '/app.db');
$pdo = new PDO('sqlite::memory:'); // handy for tests
```

### PostgreSQL (`pgsql`)

```sql
CREATE TABLE IF NOT EXISTS "migrations_versions" (
    id SERIAL PRIMARY KEY,
    version_name VARCHAR(255) NOT NULL UNIQUE,
    status INTEGER NOT NULL,
    last_time TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

```php
$pdo = new PDO('pgsql:host=localhost;port=5432;dbname=test', 'postgres', '');
```

## Identifier quoting

The version table name is validated (`^[A-Za-z_][A-Za-z0-9_]*$`) and quoted per
driver — backticks for MySQL, double quotes for SQLite/PostgreSQL — by
[`Grammar::quoteIdentifier()`](API-Reference.md#grammar).

## A note on column types and `status`

Under most PDO drivers the `status` column is read back as a **string**
(`"1"`/`"0"`), not an integer. Barbarian normalises the value to an integer
before comparing it with [`MigrationStatus`](API-Reference.md#migrationstatus), so
the lifecycle behaves identically on every driver. You never have to worry about
this when writing migrations.

## Portability of your own statements

The SQL you write inside `up()`/`down()` is yours to keep portable. To target
several engines, branch on the driver name exposed by
[`QueryRunner::driver()`](API-Reference.md#queryrunner):

```php
public function up(QueryInterface $query): bool
{
    $driver = $query instanceof \InitPHP\Barbarian\QueryRunner ? $query->driver() : null;

    $autoIncrement = match ($driver) {
        'pgsql'  => 'id SERIAL PRIMARY KEY',
        'sqlite' => 'id INTEGER PRIMARY KEY AUTOINCREMENT',
        default  => 'id INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
    };

    $query->query("CREATE TABLE example ({$autoIncrement}, name VARCHAR(255) NOT NULL)");

    return true;
}
```

## Next steps

- [Writing Migrations](Writing-Migrations.md)
- [API Reference](API-Reference.md#grammar)
- [Extending](Extending.md)

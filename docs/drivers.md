# Database drivers

Barbarian talks to your database through PDO, so any PDO driver can run your
migration statements. The only driver-specific SQL the library itself generates
is the **version table** DDL, produced by
`InitPHP\Barbarian\Schema\Grammar` based on the connection's
`PDO::ATTR_DRIVER_NAME`.

Three drivers are handled explicitly; any other driver falls back to the MySQL
grammar.

## Version table schema

The version table tracks every known migration with these columns:

| Column         | Purpose                                                |
|----------------|--------------------------------------------------------|
| `id`           | Auto-incrementing primary key.                         |
| `version_name` | The migration's version name (unique).                 |
| `status`       | `0` = down, `1` = up (see `MigrationStatus`).          |
| `last_time`    | When the row last changed.                             |

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

Connect with, e.g.:

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

Connect with a file or an in-memory database:

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

Connect with, e.g.:

```php
$pdo = new PDO('pgsql:host=localhost;dbname=test', 'postgres', '');
```

## Identifier quoting

The version table name is validated (`^[A-Za-z_][A-Za-z0-9_]*$`) and quoted per
driver — backticks for MySQL, double quotes for SQLite/PostgreSQL — by
`Grammar::quoteIdentifier()`.

## Portability of your own statements

The SQL you write inside `up()`/`down()` is yours to keep portable. If you need
to target several engines, you can branch on the driver name:

```php
public function up(QueryInterface $query): bool
{
    $driver = $query instanceof \InitPHP\Barbarian\QueryRunner
        ? $query->driver()
        : null;

    // ... emit driver-appropriate SQL ...

    return true;
}
```

> The object passed to `up()`/`down()` is the `QueryInterface`; the default
> implementation is `QueryRunner`, which also exposes `driver()` and `pdo()`.

## Next steps

- [Writing migrations](./writing-migrations.md)
- [Extending Barbarian](./extending.md)

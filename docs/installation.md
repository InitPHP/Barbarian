# Installation

## Requirements

| Requirement        | Version / Notes                                            |
|--------------------|------------------------------------------------------------|
| PHP                | 8.1 or higher                                              |
| `ext-pdo`          | Required                                                   |
| PDO driver         | `pdo_mysql`, `pdo_sqlite` or `pdo_pgsql` for your database |
| `ext-json`         | Required (used to read `barbarian.json`)                   |
| `initphp/console`  | `^2.1` (installed automatically)                           |

## Install with Composer

```bash
composer require initphp/barbarian
```

This installs the library and exposes the CLI entry point at
`vendor/bin/barbarian`.

## Verify the installation

```bash
vendor/bin/barbarian help
```

You should see the command listing (`init`, `create`, `up`, `down`).

## Autoloading and migrations

The library is autoloaded through Composer's PSR-4 mapping
(`InitPHP\Barbarian\` → `src/`). Your **migration files**, however, are
discovered by globbing `Migration_*.php` inside the configured folder and are
`require`d directly — they do **not** need to be registered with Composer's
autoloader. If your migration classes declare a namespace, tell Barbarian about
it via the [`namespace` option](./configuration.md).

## Next steps

- [Configuration](./configuration.md)
- [Writing migrations](./writing-migrations.md)

# Barbarian
Small library that can be used for database migrations.

_**Warning :** This library has not yet been fully tested and is under development. Please consider this information when using._

## Requirements

- PHP 7.4 or higher
- PHP PDO Extension
- PHP JSON Extension
- [InitPHP Console Library](https://github.com/InitPHP/Console) 

## Installation

```
composer require initphp/barbarian
```

## Usage

Start by creating a directory for the Migrations classes. 

Let your working directory be `/Migrations/` for example. 

An example migration class would look like this;

```php
<?php
declare(strict_types=1);

namespace App\Migrations;

use InitPHP\Barbarian\QueryInterface;

class Migration_20220718152243 extends \InitPHP\Barbarian\MigrationAbstract
{

    public function up(QueryInterface $query) : bool
    {
        $query->query("CREATE TABLE IF NOT EXISTS `users` (
                    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) NOT NULL,
                    `mail` varchar(255) NOT NULL,
                    `status` tinyint(1) NOT NULL DEFAULT 1,
                    PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;");
        return true;
    }
    
    public function down(QueryInterface $query) : bool
    {
        $query->query("DROP TABLE `users`");
        return true;
    }
    
}
```

### Usage in your PHP code

```php
require_once "vendor/autoload.php";

$pdo = new PDO("mysql:host=localhost;dbname=test;charset=utf8mb4", "root", "");

$migration = new \InitPHP\Barbarian\Migrations($pdo, __DIR__ . '/Migrations/', [
    'migrationTable'    => 'migrations_versions',
    'namespace'         => '\\App\\Migrations\\' // If you are not using namespace, define NULL.
]);


// Runs the up() method of a migration object.
$migration->upMigration(new \App\Migrations\Migration_20221230153013());

// Runs the down() method of a migration object.
$migration->upMigration(new \App\Migrations\Migration_20221230153013());
```

### Using the Basic CLI

To use the CLI interface, first create the "`barbarian.json`" file in your working directory and save the following structure by editing it according to you.

```json
{
  "dsn":"mysql:host=localhost;dbname=test;charset=utf8mb4",
  "username":"root",
  "password":"",
  "folder":"C:\\xampp\\Projects\\App\\Migrations\\",
  "table_name":"migrations_versions",
  "namespace":null
}
```

Or you can try to create this file with the command below.

```
php barbarian json
```

You can use the command below to up all migrations.

```
php barbarian up
```

You can use the `-version` flag to only up a migration.

```
php barbarian up -version=20221230153013
```

or

```
php barbarian up -version=Migration_20221230153013
```


You can use the command below to down all migrations.

```
php barbarian down
```

You can use the `-version` flag to only up a migration.

```
php barbarian down -version=20221230153013
```

or

```
php barbarian down -version=Migration_20221230153013
```

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>>

## License

Copyright &copy; 2022 [MIT License](./LICENSE)
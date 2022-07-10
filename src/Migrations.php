<?php
/**
 * Migrations.php
 *
 * This file is part of Barbarian.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    ./LICENSE  MIT
 * @version    1.0.1
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Barbarian;

use \PDO;

class Migrations implements QueryInterface
{

    protected const UP_STATUS      = 1;
    protected const DOWN_STATUS    = 0;

    protected ?PDO $pdo;

    protected array $options = [
        'folder'            => null,
        'namespace'         => null,
        'migrationTable'    => 'migrations_versions',
    ];

    protected string $driver;

    protected array $errors = [];

    protected array $migrations = [];

    public function __construct(PDO &$pdo, string $folder, array $options = [])
    {
        $this->setFolder($folder);
        $this->pdo = $pdo;
        foreach ($options as $key => $value) {
            if(is_numeric($key) || is_iterable($value)){
                continue;
            }
            $method = 'set' . ucfirst($key);
            if(!method_exists($this, $method)){
                continue;
            }
            $this->{$method}($value);
        }
        $this->setUp();
    }

    /**
     * @inheritDoc
     */
    public function query(string $sql, ?array $arguments = null)
    {
        if(($query = $this->pdo->prepare($sql)) === FALSE){
            return false;
        }
        if($query->execute((empty($arguments) ? null : $arguments)) === FALSE){
            return false;
        }
        return $query;
    }

    public function getMigrations(): array
    {
        return $this->migrations;
    }

    public function getError(): array
    {
        return $this->errors;
    }

    public function upMigration(MigrationInterface $migration, bool $force = false): bool
    {
        $version_name = $migration->getName();
        $query = $this->query("SELECT * FROM " . $this->options['migrationTable'] . " WHERE version_name = :version_name", [
            ':version_name'     => $version_name
        ]);
        if($query === FALSE){
            return false;
        }

        if($query->rowCount() > 0){
            $row = $query->fetch(PDO::FETCH_ASSOC);
            if((isset($row['status']) && $row['status'] === self::DOWN_STATUS) || $force === TRUE){
                call_user_func_array([$migration, 'up'], [$this]);
                $this->query("UPDATE " . $this->options['migrationTable'] . " SET status = " . self::UP_STATUS . ", last_time = '".date("c")."' WHERE version_name = :version_name", [
                    ':version_name'  => $version_name
                ]);
                return true;
            }
            return false;
        }
        call_user_func_array([$migration, 'up'], [$this]);
        $this->query("INSERT INTO " . $this->options['migrationTable'] . " (version_name, status) VALUES ('" . $version_name . "', '" . self::UP_STATUS . "');");
        return true;
    }

    public function downMigration(MigrationInterface $migration): bool
    {
        $version_name = $migration->getName();
        $query = $this->query("SELECT * FROM " . $this->options['migrationTable'] . " WHERE version_name = :version_name", [
            ':version_name'     => $version_name
        ]);
        if($query === FALSE){
            return false;
        }
        if($query->rowCount() < 1){
            return false;
        }
        $row = $query->fetch(PDO::FETCH_ASSOC);
        if(isset($row['status']) && $row['status'] === self::UP_STATUS){
            call_user_func_array([$migration, 'down'], [$this]);
            $this->query("UPDATE " . $this->options['migrationTable'] . " SET status = " . self::DOWN_STATUS . ", last_time = '".date("c")."' WHERE version_name = :version_name", [
                ':version_name'     => $version_name
            ]);
            return true;
        }
        return false;
    }

    public function getOption(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }

    protected function setMigrationTable(string $table): self
    {
        if(((bool)preg_match('/^[a-zA-Z_]+$/', $table)) === FALSE){
            throw new \InvalidArgumentException('The migration table name can only be alphabetical.');
        }
        $this->options['migrationTable'] = $table;
        return $this;
    }

    protected function setFolder(string $folder): self
    {
        if(!is_dir($folder)){
            throw new \InvalidArgumentException('The specified file location (directory) for migration files could not be found.');
        }
        $folder = rtrim($folder, "\\/") . DIRECTORY_SEPARATOR;
        $this->options['folder'] = $folder;
        return $this;
    }

    protected function setUp()
    {
        if(!isset($this->driver)){
            $this->driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        }
        $this->migration_versions_table_exists();
        $this->folder_scan();
    }

    protected function folder_scan()
    {
        if(($glob = glob($this->options['folder'] . 'Migration_*.php')) === FALSE){
            throw new MigrationException('Could not read the migration folder content.');
        }
        foreach ($glob as $file) {
            $migration = basename($file, '.php');
            $migration_full_name = empty($this->options['namespace']) ? $migration : $this->options['namespace'] . $migration;
            if(class_exists($migration_full_name)){
                $this->migrations[$migration] = $migration_full_name;
                continue;
            }
            require_once $file;
            if(class_exists($migration_full_name)){
                $this->migrations[$migration] = $migration_full_name;
                continue;
            }
            $this->errors[] = $migration_full_name . ' migration class not found.';
        }
    }

    private function migration_versions_table_exists()
    {
        switch ($this->driver) {
            case 'psql':
            case 'pgsql':
            case 'postgres':
            case 'postgresql':
                $sql = "CREATE TABLE IF NOT EXISTS " . $this->options['migrationTable'] . " (id INT NOT NULL, version_name VARCHAR(255) NOT NULL, last_time TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP, status INT NOT NULL, PRIMARY KEY (id), FOREIGN KEY (version_name));";
                break;
            case 'sqlite':
                $sql = "CREATE TABLE IF NOT EXISTS " . $this->options['migrationTable'] . " (id INTEGER PRIMARY KEY, version_name TEXT NOT NULL, last_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP, status INTEGER NOT NULL);";
                break;
            case 'mysql':
            default:
                $sql = "CREATE TABLE IF NOT EXISTS " . $this->options['migrationTable'] . " (id int(11) NOT NULL AUTO_INCREMENT, version_name varchar(255) NOT NULL, last_time datetime NOT NULL DEFAULT current_timestamp(), status int(1) NOT NULL, PRIMARY KEY (id), KEY version_name (version_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                break;
        }
        if ($this->query($sql) === FALSE) {
            throw new MigrationException('Failed to create version table.');
        }
    }

}










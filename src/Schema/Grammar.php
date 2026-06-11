<?php

/**
 * Grammar.php
 *
 * This file is part of InitPHP Barbarian.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    https://github.com/InitPHP/Barbarian/blob/main/LICENSE  MIT
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Barbarian\Schema;

/**
 * Produces the small amount of driver-specific SQL the version table needs.
 *
 * MySQL/MariaDB, SQLite and PostgreSQL are handled explicitly; any other
 * driver falls back to the MySQL grammar.
 */
final class Grammar
{
    private string $driver;

    /**
     * @param string $driver A PDO driver name such as `mysql`, `sqlite` or `pgsql`.
     */
    public function __construct(string $driver)
    {
        $this->driver = strtolower($driver);
    }

    /**
     * Wraps an identifier (such as a table name) in the driver's quoting
     * characters, escaping any embedded quote characters.
     */
    public function quoteIdentifier(string $identifier): string
    {
        return match ($this->driver) {
            'pgsql', 'sqlite' => '"' . str_replace('"', '""', $identifier) . '"',
            default => '`' . str_replace('`', '``', $identifier) . '`',
        };
    }

    /**
     * Builds the `CREATE TABLE IF NOT EXISTS` statement for the version table.
     *
     * The table records, for every known migration, its version name, current
     * {@see \InitPHP\Barbarian\MigrationStatus} and the time it last changed.
     *
     * @param string $table The (validated) version table name.
     */
    public function createVersionTable(string $table): string
    {
        $name = $this->quoteIdentifier($table);

        return match ($this->driver) {
            'pgsql' => 'CREATE TABLE IF NOT EXISTS ' . $name . ' ('
                . 'id SERIAL PRIMARY KEY, '
                . 'version_name VARCHAR(255) NOT NULL UNIQUE, '
                . 'status INTEGER NOT NULL, '
                . 'last_time TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP'
                . ');',
            'sqlite' => 'CREATE TABLE IF NOT EXISTS ' . $name . ' ('
                . 'id INTEGER PRIMARY KEY AUTOINCREMENT, '
                . 'version_name TEXT NOT NULL UNIQUE, '
                . 'status INTEGER NOT NULL, '
                . 'last_time TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP'
                . ');',
            default => 'CREATE TABLE IF NOT EXISTS ' . $name . ' ('
                . 'id INT NOT NULL AUTO_INCREMENT, '
                . 'version_name VARCHAR(255) NOT NULL, '
                . 'status INT NOT NULL, '
                . 'last_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, '
                . 'PRIMARY KEY (id), '
                . 'UNIQUE KEY uq_version_name (version_name)'
                . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
        };
    }
}

<?php

/**
 * Migrations.php
 *
 * This file is part of InitPHP Barbarian.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    https://github.com/InitPHP/Barbarian/blob/main/LICENSE  MIT
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Barbarian;

use InitPHP\Barbarian\Schema\Grammar;
use InvalidArgumentException;
use PDO;

use function array_key_exists;
use function array_keys;
use function basename;
use function class_exists;
use function date;
use function glob;
use function is_array;
use function is_dir;
use function is_subclass_of;
use function preg_match;
use function rtrim;
use function sort;
use function strrpos;

/**
 * Discovers migration classes on disk, keeps a version table in sync and runs
 * the up()/down() side of a migration on demand.
 *
 * The connection is wrapped in a {@see QueryRunner} (the {@see QueryInterface}
 * handed to each migration), so this class orchestrates migrations without
 * being a query gateway itself.
 */
final class Migrations
{
    private QueryRunner $runner;

    private Grammar $grammar;

    private string $folder;

    private ?string $namespace = null;

    private string $migrationTable = 'migrations_versions';

    /**
     * Discovered migrations, keyed by version name (the unqualified class
     * name) and mapped to the fully-qualified class name.
     *
     * @var array<string, class-string<MigrationInterface>>
     */
    private array $migrations = [];

    /**
     * Human-readable problems found while scanning the migration folder.
     *
     * @var array<int, string>
     */
    private array $errors = [];

    /**
     * @param PDO                  $pdo     The database connection.
     * @param string               $folder  Directory holding the `Migration_*.php` files.
     * @param array<string, mixed> $options Recognised keys: `namespace` (?string) and
     *                                      `migrationTable` (string). Unknown keys are ignored.
     * @throws InvalidArgumentException When the folder does not exist or the table name is invalid.
     * @throws MigrationException When the version table cannot be created or the folder cannot be read.
     */
    public function __construct(PDO $pdo, string $folder, array $options = [])
    {
        $this->runner = new QueryRunner($pdo);
        $this->grammar = new Grammar($this->runner->driver());

        $this->setFolder($folder);
        if (array_key_exists('namespace', $options)) {
            $this->setNamespace($options['namespace'] === null ? null : (string) $options['namespace']);
        }
        if (array_key_exists('migrationTable', $options)) {
            $this->setMigrationTable((string) $options['migrationTable']);
        }

        $this->ensureVersionTable();
        $this->scanFolder();
    }

    /**
     * The discovered migrations, keyed by version name.
     *
     * @return array<string, class-string<MigrationInterface>>
     */
    public function getMigrations(): array
    {
        return $this->migrations;
    }

    /**
     * Problems collected while scanning the migration folder (e.g. files that
     * did not declare the expected class).
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * The normalised migration folder (always ending in a directory separator).
     */
    public function getFolder(): string
    {
        return $this->folder;
    }

    /**
     * The namespace migration classes live under, or null when they are global.
     */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /**
     * The name of the version table.
     */
    public function getMigrationTable(): string
    {
        return $this->migrationTable;
    }

    /**
     * Applies a migration's up() side and records it as {@see MigrationStatus::Up}.
     *
     * A migration already at {@see MigrationStatus::Up} is skipped unless
     * `$force` is true. When up() returns false the state is left untouched so
     * the migration can be retried.
     *
     * @param MigrationInterface $migration The migration to apply.
     * @param bool               $force     Re-run even when already up.
     * @return bool True when up() ran and the state was recorded; false otherwise.
     * @throws \PDOException On a database error.
     */
    public function upMigration(MigrationInterface $migration, bool $force = false): bool
    {
        $versionName = $migration->getName();
        $record = $this->findRecord($versionName);

        if ($record !== null && $this->statusOf($record) === MigrationStatus::Up && $force === false) {
            return false;
        }

        if ($migration->up($this->runner) === false) {
            return false;
        }

        if ($record === null) {
            $this->insertRecord($versionName, MigrationStatus::Up);
        } else {
            $this->updateStatus($versionName, MigrationStatus::Up);
        }

        return true;
    }

    /**
     * Reverts a migration's down() side and records it as {@see MigrationStatus::Down}.
     *
     * A migration that was never applied, or is already at
     * {@see MigrationStatus::Down} (unless `$force` is true), is skipped. When
     * down() returns false the state is left untouched.
     *
     * @param MigrationInterface $migration The migration to revert.
     * @param bool               $force     Re-run even when already down.
     * @return bool True when down() ran and the state was recorded; false otherwise.
     * @throws \PDOException On a database error.
     */
    public function downMigration(MigrationInterface $migration, bool $force = false): bool
    {
        $versionName = $migration->getName();
        $record = $this->findRecord($versionName);

        if ($record === null) {
            return false;
        }
        if ($this->statusOf($record) === MigrationStatus::Down && $force === false) {
            return false;
        }

        if ($migration->down($this->runner) === false) {
            return false;
        }

        $this->updateStatus($versionName, MigrationStatus::Down);

        return true;
    }

    /**
     * Returns the recorded status of a migration, or null when it has never
     * been applied.
     *
     * @param MigrationInterface|string $migration A migration instance or its version name.
     * @throws \PDOException On a database error.
     */
    public function status(MigrationInterface|string $migration): ?MigrationStatus
    {
        $versionName = $migration instanceof MigrationInterface ? $migration->getName() : $migration;
        $record = $this->findRecord($versionName);

        return $record === null ? null : $this->statusOf($record);
    }

    /**
     * Returns the status of every discovered migration, keyed by version name.
     * A null value means the migration has not been applied yet.
     *
     * @return array<string, MigrationStatus|null>
     * @throws \PDOException On a database error.
     */
    public function getStatuses(): array
    {
        $statuses = [];
        foreach (array_keys($this->migrations) as $versionName) {
            $statuses[$versionName] = $this->status($versionName);
        }

        return $statuses;
    }

    /**
     * Validates and stores the migration folder.
     *
     * @throws InvalidArgumentException When the directory does not exist.
     */
    private function setFolder(string $folder): void
    {
        if (!is_dir($folder)) {
            throw new InvalidArgumentException(
                'The migration folder could not be found: ' . $folder
            );
        }
        $this->folder = rtrim($folder, '\\/') . DIRECTORY_SEPARATOR;
    }

    /**
     * Normalises and stores the migration namespace (without surrounding
     * backslashes); an empty namespace is treated as null.
     */
    private function setNamespace(?string $namespace): void
    {
        if ($namespace === null) {
            $this->namespace = null;

            return;
        }
        $namespace = trim($namespace, '\\');
        $this->namespace = $namespace === '' ? null : $namespace;
    }

    /**
     * Validates and stores the version table name.
     *
     * @throws InvalidArgumentException When the name is not a safe SQL identifier.
     */
    private function setMigrationTable(string $table): void
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table) !== 1) {
            throw new InvalidArgumentException(
                'The migration table name may only contain letters, digits and '
                . 'underscores and must not begin with a digit.'
            );
        }
        $this->migrationTable = $table;
    }

    /**
     * Builds the candidate fully-qualified class name for a version (short)
     * name, prefixing the configured namespace when there is one.
     */
    private function resolveClassName(string $shortName): string
    {
        return $this->namespace === null ? $shortName : $this->namespace . '\\' . $shortName;
    }

    /**
     * Scans the migration folder for `Migration_*.php` files and records the
     * migration classes they declare, collecting an error for any file that
     * does not declare a {@see MigrationInterface} implementation.
     *
     * @throws MigrationException When the folder cannot be read.
     */
    private function scanFolder(): void
    {
        $files = glob($this->folder . 'Migration_*.php');
        if ($files === false) {
            throw new MigrationException('Could not read the migration folder: ' . $this->folder);
        }
        sort($files);

        foreach ($files as $file) {
            $shortName = basename($file, '.php');
            $class = $this->resolveClassName($shortName);
            if (!class_exists($class)) {
                require_once $file;
            }
            if (!class_exists($class)) {
                $this->errors[] = $class . ' migration class not found.';

                continue;
            }
            if (!is_subclass_of($class, MigrationInterface::class)) {
                $this->errors[] = $class . ' must implement ' . MigrationInterface::class . '.';

                continue;
            }
            $this->migrations[$shortName] = $class;
        }
    }

    /**
     * Creates the version table when it does not already exist.
     *
     * @throws \PDOException On a database error.
     */
    private function ensureVersionTable(): void
    {
        $this->runner->query($this->grammar->createVersionTable($this->migrationTable));
    }

    /**
     * Fetches the version table row for a migration, or null when absent.
     *
     * @return array<string, mixed>|null
     * @throws \PDOException On a database error.
     */
    private function findRecord(string $versionName): ?array
    {
        $statement = $this->runner->query(
            'SELECT * FROM ' . $this->table() . ' WHERE version_name = :version_name',
            [':version_name' => $versionName]
        );
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Reads the status out of a version row, normalising the (possibly string)
     * column value to a {@see MigrationStatus}.
     *
     * @param array<string, mixed> $record
     */
    private function statusOf(array $record): ?MigrationStatus
    {
        return MigrationStatus::tryFrom((int) ($record['status'] ?? -1));
    }

    /**
     * Inserts a fresh version row.
     *
     * @throws \PDOException On a database error.
     */
    private function insertRecord(string $versionName, MigrationStatus $status): void
    {
        $this->runner->query(
            'INSERT INTO ' . $this->table() . ' (version_name, status, last_time) '
            . 'VALUES (:version_name, :status, :last_time)',
            [
                ':version_name' => $versionName,
                ':status'       => $status->value,
                ':last_time'    => date('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Updates the status (and last-changed time) of an existing version row.
     *
     * @throws \PDOException On a database error.
     */
    private function updateStatus(string $versionName, MigrationStatus $status): void
    {
        $this->runner->query(
            'UPDATE ' . $this->table() . ' SET status = :status, last_time = :last_time '
            . 'WHERE version_name = :version_name',
            [
                ':status'       => $status->value,
                ':last_time'    => date('Y-m-d H:i:s'),
                ':version_name' => $versionName,
            ]
        );
    }

    /**
     * The quoted version table name for use in statements.
     */
    private function table(): string
    {
        return $this->grammar->quoteIdentifier($this->migrationTable);
    }
}

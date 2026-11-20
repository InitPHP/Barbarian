<?php

/**
 * QueryInterface.php
 *
 * This file is part of InitPHP Barbarian.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    https://github.com/InitPHP/Barbarian/blob/main/LICENSE  MIT
 * @link       https://muhammetsafak.com
 */

declare(strict_types=1);

namespace InitPHP\Barbarian;

use PDOStatement;

/**
 * The query gateway handed to a migration's {@see MigrationInterface::up()} and
 * {@see MigrationInterface::down()} methods.
 *
 * Implementations execute a (optionally parameterised) statement against the
 * underlying connection and return the executed {@see PDOStatement} so callers
 * may fetch results when needed. Failures are reported by throwing rather than
 * by returning a falsy value.
 */
interface QueryInterface
{
    /**
     * Prepares and executes an SQL statement.
     *
     * @param string                    $sql       The SQL statement to run.
     * @param array<int|string, mixed>|null $arguments Bound parameters for the prepared statement,
     *                                             or null when the statement takes none.
     * @return PDOStatement The executed statement.
     * @throws \PDOException When the statement cannot be prepared or executed.
     */
    public function query(string $sql, ?array $arguments = null): PDOStatement;
}

<?php

/**
 * QueryRunner.php
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

use PDO;
use PDOStatement;

/**
 * The default {@see QueryInterface} implementation: a thin wrapper around a
 * PDO connection.
 *
 * The wrapped connection is switched to {@see PDO::ERRMODE_EXCEPTION} so that
 * every failure surfaces as a {@see \PDOException} regardless of how the caller
 * originally configured PDO. This guarantees the uniform error model the rest
 * of the library relies on.
 */
final class QueryRunner implements QueryInterface
{
    private PDO $pdo;

    /**
     * @param PDO $pdo The connection to run statements against. Its error mode
     *                 is forced to {@see PDO::ERRMODE_EXCEPTION}.
     */
    public function __construct(PDO $pdo)
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo = $pdo;
    }

    /**
     * The underlying PDO connection.
     */
    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * The name of the PDO driver in use (e.g. `mysql`, `sqlite`, `pgsql`).
     */
    public function driver(): string
    {
        return (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * {@inheritDoc}
     */
    public function query(string $sql, ?array $arguments = null): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        if ($statement === false) {
            throw new \PDOException('Failed to prepare the statement: ' . $sql);
        }
        $statement->execute(empty($arguments) ? null : $arguments);

        return $statement;
    }
}

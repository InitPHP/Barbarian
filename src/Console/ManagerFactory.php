<?php

/**
 * ManagerFactory.php
 *
 * This file is part of InitPHP Barbarian.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    https://github.com/InitPHP/Barbarian/blob/main/LICENSE  MIT
 * @link       https://muhammetsafak.com
 */

declare(strict_types=1);

namespace InitPHP\Barbarian\Console;

use InitPHP\Barbarian\MigrationException;
use InitPHP\Barbarian\Migrations;
use PDO;
use PDOException;

/**
 * Builds a configured {@see Migrations} manager (and the PDO connection it
 * needs) from a {@see Config}.
 */
final class ManagerFactory
{
    /**
     * Connects to the database described by `$config` and returns a ready
     * {@see Migrations} manager.
     *
     * @throws MigrationException When the connection cannot be opened.
     * @throws \InvalidArgumentException When the configured folder/table is invalid.
     */
    public static function fromConfig(Config $config): Migrations
    {
        try {
            $pdo = new PDO($config->dsn, $config->username, $config->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $exception) {
            throw new MigrationException(
                'Database connection failed: ' . $exception->getMessage(),
                (int) $exception->getCode(),
                $exception
            );
        }

        return new Migrations($pdo, $config->folder, [
            'migrationTable' => $config->migrationTable,
            'namespace'      => $config->namespace,
        ]);
    }
}

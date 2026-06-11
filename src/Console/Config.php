<?php

/**
 * Config.php
 *
 * This file is part of InitPHP Barbarian.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    https://github.com/InitPHP/Barbarian/blob/main/LICENSE  MIT
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Barbarian\Console;

/**
 * Immutable, validated representation of a `barbarian.json` configuration file.
 *
 * @see ConfigLoader for how an instance is built from a file on disk.
 */
final class Config
{
    public function __construct(
        public readonly string $dsn,
        public readonly string $username,
        public readonly string $password,
        public readonly string $folder,
        public readonly string $migrationTable,
        public readonly ?string $namespace
    ) {
    }
}

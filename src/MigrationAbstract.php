<?php

/**
 * MigrationAbstract.php
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

/**
 * Convenience base class for migrations.
 *
 * It implements {@see getName()} by deriving the version name from the
 * (unqualified) class name, leaving subclasses to implement only {@see up()}
 * and {@see down()}.
 */
abstract class MigrationAbstract implements MigrationInterface
{
    /**
     * {@inheritDoc}
     */
    abstract public function up(QueryInterface $query): bool;

    /**
     * {@inheritDoc}
     */
    abstract public function down(QueryInterface $query): bool;

    /**
     * {@inheritDoc}
     *
     * Returns the class name without its namespace prefix, so a class named
     * `App\Migrations\Migration_20221230153013` is tracked as
     * `Migration_20221230153013`.
     */
    final public function getName(): string
    {
        $class = static::class;
        $position = strrpos($class, '\\');

        return $position === false ? $class : substr($class, $position + 1);
    }
}

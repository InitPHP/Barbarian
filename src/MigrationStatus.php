<?php

/**
 * MigrationStatus.php
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

/**
 * The persisted state of a migration in the version table.
 *
 * The backing integer is what is stored in (and read back from) the `status`
 * column, so comparisons are made on {@see self::value} and never rely on the
 * PDO driver returning a particular PHP type for the column.
 */
enum MigrationStatus: int
{
    /** The migration's down() method was the last one applied. */
    case Down = 0;

    /** The migration's up() method was the last one applied. */
    case Up = 1;
}

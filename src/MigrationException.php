<?php

/**
 * MigrationException.php
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

use Exception;

/**
 * Raised for failures originating in Barbarian itself — an unreadable
 * migration folder, an invalid configuration file, a failed connection — as
 * opposed to {@see \PDOException}s raised while executing migration statements.
 */
class MigrationException extends Exception {}

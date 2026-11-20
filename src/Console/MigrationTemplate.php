<?php

/**
 * MigrationTemplate.php
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

use function implode;
use function trim;

/**
 * Renders the PHP source for a new, empty migration class.
 */
final class MigrationTemplate
{
    /**
     * Builds the source of a migration class extending
     * {@see \InitPHP\Barbarian\MigrationAbstract}.
     *
     * @param string      $className The class (and version) name, e.g. `Migration_20221230153013`.
     * @param string|null $namespace The namespace to place the class in, or null for the global namespace.
     */
    public static function render(string $className, ?string $namespace): string
    {
        $lines = ['<?php', '', 'declare(strict_types=1);', ''];

        $namespace = $namespace === null ? null : trim($namespace, '\\');
        if ($namespace !== null && $namespace !== '') {
            $lines[] = 'namespace ' . $namespace . ';';
            $lines[] = '';
        }

        $lines[] = 'use InitPHP\\Barbarian\\MigrationAbstract;';
        $lines[] = 'use InitPHP\\Barbarian\\QueryInterface;';
        $lines[] = '';
        $lines[] = 'class ' . $className . ' extends MigrationAbstract';
        $lines[] = '{';
        $lines[] = '    public function up(QueryInterface $query): bool';
        $lines[] = '    {';
        $lines[] = '        return true;';
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    public function down(QueryInterface $query): bool';
        $lines[] = '    {';
        $lines[] = '        return true;';
        $lines[] = '    }';
        $lines[] = '}';
        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }
}

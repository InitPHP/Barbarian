<?php

/**
 * InitCommand.php
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

use InitPHP\Console\Command;
use InitPHP\Console\Input;
use InitPHP\Console\InputArgument;
use InitPHP\Console\Output;

use function file_put_contents;
use function getcwd;
use function is_dir;
use function is_file;
use function is_string;
use function json_encode;
use function mkdir;
use function rtrim;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * `barbarian init` — scaffolds a `barbarian.json` configuration file.
 *
 * Requires no database connection.
 */
final class InitCommand extends Command
{
    /** @var string */
    public $command = 'init';

    public function definition(): string
    {
        return 'Generates a barbarian.json configuration file.';
    }

    public function help(): string
    {
        return 'Writes a barbarian.json template into the given directory '
            . '(the current working directory by default).';
    }

    /**
     * @return array<int, InputArgument>
     */
    public function arguments(): array
    {
        return [
            new InputArgument(
                'dir',
                InputArgument::STR,
                '',
                true,
                'Directory to create barbarian.json in (defaults to the current directory).',
            ),
        ];
    }

    public function execute(Input $input, Output $output): void
    {
        $dir = $input->getArgument('dir');
        if (!is_string($dir) || $dir === '') {
            $cwd = getcwd();
            $dir = $cwd === false ? '.' : $cwd;
        }
        $base = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
        $path = $base . 'barbarian.json';
        $migrationFolder = $base . 'migrations' . DIRECTORY_SEPARATOR;

        if (is_file($path)) {
            $output->warning('A configuration file already exists: ' . $path);

            return;
        }

        $template = [
            'dsn'        => 'mysql:host=localhost;dbname=test;charset=utf8mb4',
            'username'   => 'root',
            'password'   => '',
            'folder'     => $migrationFolder,
            'table_name' => 'migrations_versions',
            'namespace'  => null,
        ];

        $json = json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false || @file_put_contents($path, $json . PHP_EOL) === false) {
            $output->error('Failed to create the configuration file: ' . $path);

            return;
        }

        $output->success('Configuration file created: ' . $path);

        if (!is_dir($migrationFolder) && @mkdir($migrationFolder, 0o777, true) === false && !is_dir($migrationFolder)) {
            $output->warning('Could not create the migration folder: ' . $migrationFolder);

            return;
        }
        $output->info('Migration folder ready: ' . $migrationFolder);
    }
}

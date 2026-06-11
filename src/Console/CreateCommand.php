<?php

/**
 * CreateCommand.php
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

use InitPHP\Console\Input;
use InitPHP\Console\InputArgument;
use InitPHP\Console\Output;

use function date;
use function file_put_contents;
use function is_dir;
use function is_file;
use function rtrim;

/**
 * `barbarian create` — scaffolds a new, empty migration class in the folder
 * configured in `barbarian.json`.
 *
 * Reads only the configuration file; it does not open a database connection.
 */
final class CreateCommand extends BarbarianCommand
{
    /** @var string */
    public $command = 'create';

    public function definition(): string
    {
        return 'Creates a new migration class.';
    }

    public function help(): string
    {
        return 'Generates a timestamped Migration_<YmdHis> class in the configured '
            . 'migration folder, using the configured namespace when one is set.';
    }

    /**
     * @return array<int, InputArgument>
     */
    public function arguments(): array
    {
        return [
            $this->configArgument(),
        ];
    }

    public function execute(Input $input, Output $output): void
    {
        $config = $this->loadConfig($input, $output);
        if ($config === null) {
            return;
        }

        $folder = $config->folder;
        if (!is_dir($folder)) {
            $output->error('The migration folder does not exist: ' . $folder);

            return;
        }

        $name = 'Migration_' . date('YmdHis');
        $path = rtrim($folder, '/\\') . DIRECTORY_SEPARATOR . $name . '.php';
        if (is_file($path)) {
            $output->warning('A migration with this name already exists: ' . $name);

            return;
        }

        $content = MigrationTemplate::render($name, $config->namespace);
        if (@file_put_contents($path, $content) === false) {
            $output->error('Failed to create the migration file: ' . $path);

            return;
        }

        $output->success('Migration created: ' . $name);
    }
}

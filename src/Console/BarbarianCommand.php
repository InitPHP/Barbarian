<?php

/**
 * BarbarianCommand.php
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

use InitPHP\Console\Command;
use InitPHP\Console\Input;
use InitPHP\Console\InputArgument;
use InitPHP\Console\Output;

use function getcwd;
use function is_file;
use function is_string;

/**
 * Base class for the configuration-aware Barbarian commands.
 *
 * Provides the shared `--config` argument and the logic that locates and loads
 * the `barbarian.json` file.
 */
abstract class BarbarianCommand extends Command
{
    /**
     * The `--config` argument every configuration-aware command accepts.
     */
    protected function configArgument(): InputArgument
    {
        return new InputArgument(
            'config',
            InputArgument::STR,
            '',
            true,
            'Path to the barbarian.json file (defaults to ./barbarian.json).'
        );
    }

    /**
     * Resolves the configuration file path from the `--config` argument,
     * falling back to `barbarian.json` in the current working directory.
     */
    protected function resolveConfigPath(Input $input): string
    {
        $path = $input->getArgument('config');
        if (is_string($path) && $path !== '') {
            return $path;
        }
        $cwd = getcwd();

        return ($cwd === false ? '.' : $cwd) . DIRECTORY_SEPARATOR . 'barbarian.json';
    }

    /**
     * Loads the configuration, writing a helpful error and returning null when
     * the file does not exist.
     *
     * @throws \InitPHP\Barbarian\MigrationException When the file exists but cannot be parsed.
     */
    protected function loadConfig(Input $input, Output $output): ?Config
    {
        $path = $this->resolveConfigPath($input);
        if (!is_file($path)) {
            $output->error('Configuration file not found: ' . $path);
            $output->info('Run "barbarian init" to generate a barbarian.json file.');

            return null;
        }

        return ConfigLoader::fromFile($path);
    }
}

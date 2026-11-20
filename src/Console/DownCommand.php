<?php

/**
 * DownCommand.php
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

use InitPHP\Barbarian\MigrationInterface;
use InitPHP\Barbarian\Migrations;
use InitPHP\Console\Input;
use InitPHP\Console\InputArgument;
use InitPHP\Console\Output;

use function array_reverse;
use function is_string;

/**
 * `barbarian down` — reverts migrations.
 *
 * With no `--version` it reverts every discovered migration in descending
 * order (the reverse of how they were applied); with `--version` it reverts a
 * single migration.
 */
final class DownCommand extends BarbarianCommand
{
    /** @var string */
    public $command = 'down';

    public function definition(): string
    {
        return 'Runs the down() method of the migrations.';
    }

    public function help(): string
    {
        return 'Reverts all migrations in reverse order, or a single one when '
            . '--version is given, e.g. --version=Migration_20221230153013 or '
            . '--version=20221230153013.';
    }

    /**
     * @return array<int, InputArgument>
     */
    public function arguments(): array
    {
        return [
            new InputArgument('version', InputArgument::STR, '', true, 'A single migration version to revert.'),
            $this->configArgument(),
        ];
    }

    public function execute(Input $input, Output $output): void
    {
        $config = $this->loadConfig($input, $output);
        if ($config === null) {
            return;
        }

        $manager = ManagerFactory::fromConfig($config);
        foreach ($manager->getErrors() as $error) {
            $output->warning($error);
        }

        $migrations = $manager->getMigrations();
        $version = $input->getArgument('version');

        if (is_string($version) && $version !== '') {
            $class = $this->resolveVersion($migrations, $version);
            if ($class === null) {
                $output->error('The requested migration version could not be found: ' . $version);
                $output->info('Usage: barbarian down --version=20221230153013');

                return;
            }
            $this->revert($manager, $class, $output, true);

            return;
        }

        if ($migrations === []) {
            $output->info('No migrations found.');

            return;
        }
        foreach (array_reverse($migrations) as $class) {
            $this->revert($manager, $class, $output, false);
        }
    }

    /**
     * @param array<string, class-string<MigrationInterface>> $migrations
     * @return class-string<MigrationInterface>|null
     */
    private function resolveVersion(array $migrations, string $version): ?string
    {
        return $migrations[$version] ?? $migrations['Migration_' . $version] ?? null;
    }

    /**
     * @param class-string<MigrationInterface> $class
     */
    private function revert(Migrations $manager, string $class, Output $output, bool $force): void
    {
        $migration = new $class();
        if ($manager->downMigration($migration, $force)) {
            $output->success($class . '::down() has been executed.');

            return;
        }
        $output->info('Skipped ' . $class . '::down() (already down or it returned false).');
    }
}
